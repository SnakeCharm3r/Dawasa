from __future__ import annotations

from typing import Any, Dict, List, Optional

from src.analysis.dom_analyzer import DomAnalyzer
from src.analysis.imbalance import ImbalanceAnalyzer
from src.analysis.liquidity import LiquidityAnalyzer
from src.analysis.pressure import PressureAnalyzer
from src.analysis.spread import SpreadAnalyzer
from src.analysis.volatility import VolatilityAnalyzer
from src.utils.price import PriceNormalizer, build_normalizer_from_config
from src.config import config


class AnalysisListener:
    """Event-driven consumer that turns ``DepthUpdated`` events into metrics.

    Subscribes to the existing ``EventBus`` and, for every depth update,
    runs the full set of analyzers. Results are persisted through the
    storage layer. The collector is unaware of this class, keeping analysis
    fully decoupled from data acquisition.

    Prices are normalized using the same logic as the console monitor so the
    persisted history reflects real-world prices (cTrader delivers integer
    scaled values).
    """

    def __init__(
        self,
        event_bus: Any,
        db: Optional[Any] = None,
        symbol_store: Optional[Any] = None,
        logger: Optional[Any] = None,
        normalizer: Optional[PriceNormalizer] = None,
    ) -> None:
        self.event_bus = event_bus
        self.db = db
        self.symbol_store = symbol_store
        self.logger = logger
        self._normalizer = normalizer or build_normalizer_from_config(
            config, getattr(config, "FOCUS_SYMBOL", "XAUUSD")
        )

        self.dom_analyzer = DomAnalyzer()
        self.imbalance_analyzer = ImbalanceAnalyzer()
        self.pressure_analyzer = PressureAnalyzer()
        self.liquidity_analyzer = LiquidityAnalyzer()
        self.volatility_analyzer = VolatilityAnalyzer()
        self.spread_analyzer = SpreadAnalyzer()

        self.event_bus.subscribe("DepthUpdated", self.on_depth_updated)

    def _symbol_id(self, symbol_name: str) -> Optional[int]:
        if self.symbol_store is None:
            return None
        symbol = self.symbol_store.get(symbol_name)
        if symbol is None:
            return None
        return symbol.get("symbol_id")

    def on_depth_updated(self, payload: Dict[str, Any]) -> None:
        symbol_name = payload.get("symbol")
        if not symbol_name:
            return

        depth = payload.get("depth")
        top_of_book = payload.get("top_of_book") or {}

        metrics = self._compute_metrics(depth, top_of_book, symbol_name)

        if self.logger:
            self.logger.info(
                f"Analyzed depth for {symbol_name}: {len(metrics)} metrics"
            )

        if self.db is None:
            return

        symbol_id = self._symbol_id(symbol_name)
        if symbol_id is None:
            return

        for metric_name, metric_value in metrics.items():
            self.db.upsert_metric(
                {
                    "symbol_id": symbol_id,
                    "metric_name": metric_name,
                    "metric_value": float(metric_value),
                }
            )

        self._persist_ladder(symbol_id, symbol_name, depth)

    def _persist_ladder(self, symbol_id: int, symbol_name: str, depth: Optional[Dict[str, Any]]) -> None:
        """Persist the normalized order-book ladder so the web UI can render it."""
        if not depth:
            return
        levels: List[Dict[str, Any]] = []
        for side in ("bids", "asks"):
            for lvl in depth.get(side, []) or []:
                price = self._normalizer.normalize(lvl.get("price"), symbol_name)
                size = lvl.get("size")
                qid = lvl.get("id")
                if price is None or size is None or qid is None:
                    continue
                levels.append({
                    "side": "bid" if side == "bids" else "ask",
                    "quote_id": int(qid),
                    "price": float(price),
                    "size": float(size),
                })
        if levels:
            self.db.replace_depth_snapshots(symbol_id, levels)

    def _compute_metrics(self, depth: Optional[Dict[str, Any]], top_of_book: Dict[str, float], symbol_name: str = "") -> Dict[str, float]:
        raw_bid = top_of_book.get("bid")
        raw_ask = top_of_book.get("ask")
        bid = self._normalizer.normalize(raw_bid, symbol_name) or 0.0
        ask = self._normalizer.normalize(raw_ask, symbol_name) or 0.0
        spread = ask - bid if ask and bid else 0.0

        tick_payload = {"bid": bid, "ask": ask, "spread": spread}

        metrics: Dict[str, float] = {
            "imbalance": self.imbalance_analyzer.analyze(tick_payload),
            "pressure": self.pressure_analyzer.analyze(tick_payload),
            "liquidity": self.liquidity_analyzer.analyze(tick_payload),
            "volatility": self.volatility_analyzer.analyze(tick_payload),
            "spread": self.spread_analyzer.analyze(tick_payload),
        }

        dom = self.dom_analyzer.analyze(depth)
        for name, value in dom.items():
            metrics[f"dom_{name}"] = float(value)

        return metrics
