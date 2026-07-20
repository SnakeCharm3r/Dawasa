from __future__ import annotations

import logging
import threading
import time
from typing import Any, Dict, List, Optional

from rich.console import Console
from rich.table import Table

from src.analysis.dom_analyzer import DomAnalyzer
from src.analysis.imbalance import ImbalanceAnalyzer
from src.analysis.liquidity import LiquidityAnalyzer
from src.analysis.pressure import PressureAnalyzer
from src.analysis.spread import SpreadAnalyzer
from src.analysis.volatility import VolatilityAnalyzer
from src.utils.price import PriceNormalizer


class DomMonitor:
    """Console order-book monitor driven by ``EventBus`` events.

    Subscribes to the existing ``EventBus`` and keeps only the latest snapshot
    in memory, plus the last traded price from ``TradeExecuted`` events. A
    background thread renders at a fixed cadence via ``rich.Live`` so the
    terminal is refreshed in place instead of flooding with new lines.

    Raw cTrader prices are integer-scaled by 10 ** digits (per symbol). This
    monitor normalizes them using each symbol's configured ``digits`` value,
    validates every level (dropping invalid/duplicate entries), and re-sorts
    the book so asks are ascending and bids are descending. It never modifies
    collectors, the EventBus, or the cTrader connection.
    """

    def __init__(
        self,
        event_bus: Any,
        symbol: str = "XAUUSD",
        max_levels: int = 10,
        refresh_interval: float = 0.5,
        digits: int = 0,
        digits_by_symbol: Optional[Dict[str, int]] = None,
        normalizer: Optional[PriceNormalizer] = None,
        logger: Optional[Any] = None,
    ) -> None:
        self.event_bus = event_bus
        self.symbol = symbol
        self.max_levels = max_levels
        self.refresh_interval = refresh_interval

        self._digits_by_symbol: Dict[str, int] = dict(digits_by_symbol or {})
        if symbol not in self._digits_by_symbol:
            self._digits_by_symbol[symbol] = int(digits)
        self._normalizer = normalizer or PriceNormalizer(self._digits_by_symbol)

        self._logger = logger or logging.getLogger("DomMonitor")

        self.dom_analyzer = DomAnalyzer()
        self.imbalance_analyzer = ImbalanceAnalyzer()
        self.pressure_analyzer = PressureAnalyzer()
        self.liquidity_analyzer = LiquidityAnalyzer()
        self.volatility_analyzer = VolatilityAnalyzer()
        self.spread_analyzer = SpreadAnalyzer()

        self._lock = threading.Lock()
        self._snapshot: Optional[Dict[str, Any]] = None
        self._latest_metrics: Dict[str, Any] = {}
        self._last_trade: Optional[Dict[str, Any]] = None
        self._last_depth_ts: Optional[float] = None
        self._last_trade_ts: Optional[float] = None
        self._order_flow: Optional[Dict[str, Any]] = None
        self._console = Console()
        self._live: Optional[Any] = None
        self._stop = threading.Event()
        self._render_thread: Optional[threading.Thread] = None

        self.event_bus.subscribe("DepthUpdated", self.on_depth_updated)
        self.event_bus.subscribe("TradeExecuted", self.on_trade_executed)
        self.event_bus.subscribe("OrderFlowSignal", self.on_order_flow)

    # ------------------------------------------------------------------
    # Price normalization
    # ------------------------------------------------------------------
    def digits_for(self, symbol: str) -> int:
        return self._normalizer.digits_for(symbol)

    def normalize_price(self, raw_price: Any, symbol: str) -> Optional[float]:
        """Convert a raw cTrader integer price to a normalized float.

        Returns ``None`` when the raw value is missing or not numeric.
        """
        if raw_price is None:
            return None
        try:
            raw = float(raw_price)
        except (TypeError, ValueError):
            self._logger.warning("Invalid raw price (not numeric): %r", raw_price)
            return None

        normalized = self._normalizer.normalize(raw_price, symbol)
        if normalized is None:
            return None
        self._logger.debug(
            "Normalized %s: raw=%s -> %s",
            symbol,
            raw,
            normalized,
        )
        return normalized

    # ------------------------------------------------------------------
    # Event handlers
    # ------------------------------------------------------------------
    def on_depth_updated(self, payload: Dict[str, Any]) -> None:
        if payload.get("symbol") != self.symbol:
            return
        try:
            with self._lock:
                self._snapshot = payload.get("depth")
                self._last_depth_ts = time.time()
                self._latest_metrics = self._compute_metrics(payload)
        except Exception as exc:  # pragma: no cover - defensive
            self._logger.warning("Failed to process DepthUpdated: %s", exc)

    def on_trade_executed(self, payload: Dict[str, Any]) -> None:
        if payload.get("symbol") != self.symbol:
            return
        try:
            with self._lock:
                raw_price = payload.get("price")
                self._last_trade = {
                    "price": self.normalize_price(raw_price, self.symbol),
                    "volume": payload.get("volume"),
                    "side": payload.get("side"),
                    "timestamp": payload.get("timestamp"),
                }
                self._last_trade_ts = time.time()
        except Exception as exc:  # pragma: no cover - defensive
            self._logger.warning("Failed to process TradeExecuted: %s", exc)

    def on_order_flow(self, payload: Dict[str, Any]) -> None:
        if payload.get("symbol") != self.symbol:
            return
        with self._lock:
            self._order_flow = payload

    # ------------------------------------------------------------------
    # Level building / validation
    # ------------------------------------------------------------------
    def _build_levels(self, raw_levels: List[Dict[str, Any]], side: str) -> List[Dict[str, Any]]:
        """Validate, dedupe by id, normalize, and sort a side of the book."""
        cleaned: Dict[Any, Dict[str, Any]] = {}
        seen_ids = set()
        for level in raw_levels or []:
            try:
                qid = level.get("id")
                if qid is not None:
                    if qid in seen_ids:
                        self._logger.warning("Duplicate quote id %s on %s; keeping latest", qid, side)
                    seen_ids.add(qid)

                price = self.normalize_price(level.get("price"), self.symbol)
                size = level.get("size")
                try:
                    size = float(size) if size is not None else 0.0
                except (TypeError, ValueError):
                    self._logger.warning("Invalid size %r on %s; dropping level", size, side)
                    continue

                if price is None or price <= 0:
                    self._logger.warning("Invalid price %r on %s; dropping level", level.get("price"), side)
                    continue
                if size <= 0:
                    self._logger.warning("Non-positive size %s on %s; dropping level", size, side)
                    continue

                cleaned[qid] = {"id": qid, "price": price, "size": size}
            except Exception as exc:  # pragma: no cover - defensive
                self._logger.warning("Malformed %s level %r skipped: %s", side, level, exc)
                continue

        levels = list(cleaned.values())
        if side == "asks":
            levels.sort(key=lambda lvl: lvl["price"])  # ascending
        else:
            levels.sort(key=lambda lvl: lvl["price"], reverse=True)  # descending
        return levels

    # ------------------------------------------------------------------
    # Metrics (reuse existing analyzers instead of recalculating)
    # ------------------------------------------------------------------
    def _compute_metrics(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        depth = payload.get("depth") or {}
        bids = self._build_levels(depth.get("bids", []), "bids")
        asks = self._build_levels(depth.get("asks", []), "asks")

        best_bid = bids[0]["price"] if bids else 0.0
        best_ask = asks[0]["price"] if asks else 0.0
        mid = (best_bid + best_ask) / 2.0 if (best_bid or best_ask) else 0.0
        spread = (best_ask - best_bid) if (best_bid and best_ask) else 0.0

        tick_payload = {"bid": best_bid, "ask": best_ask, "spread": spread}
        dom = self.dom_analyzer.analyze({"bids": bids, "asks": asks})

        return {
            "best_bid": best_bid,
            "best_ask": best_ask,
            "mid": mid,
            "spread": self.spread_analyzer.analyze(tick_payload),
            "bid_volume": dom.get("bid_volume", 0.0),
            "ask_volume": dom.get("ask_volume", 0.0),
            "imbalance": self.imbalance_analyzer.analyze(tick_payload),
            "pressure": self.pressure_analyzer.analyze(tick_payload),
            "liquidity": self.liquidity_analyzer.analyze(tick_payload),
            "volatility": self.volatility_analyzer.analyze(tick_payload),
        }

    # ------------------------------------------------------------------
    # Rendering
    # ------------------------------------------------------------------
    def _render(self) -> Table:
        table = Table(title=f"DOM Monitor — {self.symbol}", show_header=True, header_style="bold magenta")
        table.add_column("Side", style="cyan", width=6)
        table.add_column("Price", justify="right", style="green")
        table.add_column("Size", justify="right", style="yellow")

        with self._lock:
            snapshot = self._snapshot
            metrics = dict(self._latest_metrics)
            last_trade = self._last_trade
            last_depth_ts = self._last_depth_ts
            last_trade_ts = self._last_trade_ts
            order_flow = self._order_flow

        whale_ids = set()
        if order_flow:
            for whale in order_flow.get("whales", []) or []:
                wid = whale.get("id")
                if wid is not None:
                    whale_ids.add(wid)

        depth = (snapshot or {}).get("depth") if snapshot else None
        if depth is None:
            depth = snapshot or {}
        bids = self._build_levels(depth.get("bids", []), "bids")[: self.max_levels]
        asks = self._build_levels(depth.get("asks", []), "asks")[: self.max_levels]

        for level in asks:
            style = "bold green on rgb(0,70,0)" if level.get("id") in whale_ids else None
            table.add_row("ASK", f"{level['price']:,.2f}", f"{level['size']:,.0f}", style=style)

        best_bid = bids[0]["price"] if bids else None
        best_ask = asks[0]["price"] if asks else None
        last_price = last_trade.get("price") if last_trade else None

        if last_price is None:
            table.add_row("LAST", "No execution data available", "-", style="bold white on red")
        else:
            side_label = "—"
            if last_trade and last_trade.get("side") is not None:
                side_label = str(last_trade.get("side"))
            elif best_bid is not None and best_ask is not None:
                side_label = "BUY" if last_price >= best_ask else ("SELL" if last_price <= best_bid else "AT")
            last_size = f"{float(last_trade['volume']):,.0f}" if last_trade and last_trade.get("volume") is not None else "-"
            table.add_row("LAST", f"{last_price:,.2f} ({side_label})", last_size, style="bold white on blue")

        for level in bids:
            style = "bold green on rgb(0,70,0)" if level.get("id") in whale_ids else None
            table.add_row("BID", f"{level['price']:,.2f}", f"{level['size']:,.0f}", style=style)

        if not bids and not asks:
            table.add_row("-", "waiting for data…", "-")

        # Order-flow signal printouts
        signals: List[str] = []
        if order_flow:
            absb = order_flow.get("absorption")
            if absb:
                signals.append(
                    f"[bold green]ABSORPTION[/bold green] {absb['direction'].upper()} side "
                    f"vol={absb['volume']:,.0f} vs book={absb['side_total']:,.0f}"
                )
            spoof = order_flow.get("spoofing")
            if spoof:
                signals.append(
                    f"[bold red]SPOOFING[/bold red] {len(spoof.get('pulled', []))} large order(s) pulled"
                )
            div = order_flow.get("divergence")
            if div:
                signals.append(
                    f"[bold yellow]DIVERGENCE[/bold yellow] {div['type'].upper()} "
                    f"flow={div['net_flow']:+.0f} priceΔ={div['price_delta']:+.2f}"
                )
        for line in signals:
            table.add_row("SIG", line, "", style="bold")

        ts = last_depth_ts or last_trade_ts
        ts_str = time.strftime("%H:%M:%S", time.localtime(ts)) if ts else "—"

        table.caption = (
            f"bestBid={metrics.get('best_bid', 0.0):,.2f}  "
            f"bestAsk={metrics.get('best_ask', 0.0):,.2f}  "
            f"mid={metrics.get('mid', 0.0):,.2f}  "
            f"spread={metrics.get('spread', 0.0):.2f}  "
            f"bidVol={metrics.get('bid_volume', 0.0):,.0f}  "
            f"askVol={metrics.get('ask_volume', 0.0):,.0f}  "
            f"imbalance={metrics.get('imbalance', 0.0):+.3f}  "
            f"pressure={metrics.get('pressure', 0.0):+.3f}  "
            f"liquidity={metrics.get('liquidity', 0.0):.4f}  "
            f"volatility={metrics.get('volatility', 0.0):.2f}  "
            f"updated={ts_str}"
        )
        return table

    def _loop(self) -> None:
        from rich.live import Live

        with Live(self._render(), console=self._console, refresh_per_second=1 / self.refresh_interval) as live:
            self._live = live
            while not self._stop.is_set():
                time.sleep(self.refresh_interval)
                live.update(self._render())

    def start(self) -> None:
        if self._render_thread is not None:
            return
        self._stop.clear()
        self._render_thread = threading.Thread(target=self._loop, daemon=True)
        self._render_thread.start()

    def stop(self) -> None:
        self._stop.set()
        if self._render_thread is not None:
            self._render_thread.join(timeout=self.refresh_interval + 1.0)
            self._render_thread = None
        self._live = None
