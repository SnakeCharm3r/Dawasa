from __future__ import annotations

import logging
import time
from collections import deque
from typing import Any, Deque, Dict, List, Optional


def _side_is_buy(side: Any) -> Optional[bool]:
    """Normalize a trade side into a boolean (True=buy/aggressor-bid)."""
    if side is None:
        return None
    if isinstance(side, str):
        s = side.strip().upper()
        if s in ("BUY", "B"):
            return True
        if s in ("SELL", "S"):
            return False
        return None
    # numeric enum: 1=BUY, 2=SELL in cTrader ProtoOATradeSide
    if side == 1:
        return True
    if side == 2:
        return False
    return None


class OrderFlowAnalyzer:
    """Event-driven order-flow analytics.

    Subscribes to the existing ``DepthUpdated`` and ``TradeExecuted`` events and
    derives four signals without touching collectors or the cTrader link:

    * **Whales** — large resting orders currently sitting on the book.
    * **Absorption** — a large market order consumed by limit liquidity
      (big trade, book on the hit side barely moves).
    * **Spoofing** — a large resting order that appears then is pulled
      quickly with little or no trade at its price.
    * **Divergence** — trade-flow imbalance disagreeing with price direction.

    Results are published as an ``OrderFlowSignal`` event and cached per symbol
    for the DOM monitor to render.
    """

    def __init__(
        self,
        event_bus: Any,
        symbol: Optional[str] = None,
        whale_size: float = 500.0,
        absorption_volume: float = 300.0,
        spoof_size: float = 500.0,
        spoof_window: float = 2.0,
        trade_window: float = 5.0,
        logger: Optional[Any] = None,
    ) -> None:
        self.event_bus = event_bus
        self.symbol = symbol
        self.whale_size = whale_size
        self.absorption_volume = absorption_volume
        self.spoof_size = spoof_size
        self.spoof_window = spoof_window
        self.trade_window = trade_window
        self._logger = logger or logging.getLogger("OrderFlowAnalyzer")

        self._state: Dict[str, Dict[str, Any]] = {}
        self._latest: Dict[str, Dict[str, Any]] = {}

        self.event_bus.subscribe("DepthUpdated", self.on_depth_updated)
        self.event_bus.subscribe("TradeExecuted", self.on_trade_executed)

    # ------------------------------------------------------------------
    # State
    # ------------------------------------------------------------------
    def _state_for(self, symbol: str) -> Dict[str, Any]:
        st = self._state.get(symbol)
        if st is None:
            st = {
                "prev_bids": {},  # id -> size
                "prev_asks": {},
                "book_total_bid": 0.0,
                "book_total_ask": 0.0,
                "whales": {},  # id -> {side, price, size}
                "trades": deque(maxlen=300),
                "pulled_large": [],  # (id, side, price, size, ts)
                "last_ts": None,
            }
            self._state[symbol] = st
        return st

    # ------------------------------------------------------------------
    # Depth updates -> whales + spoofing
    # ------------------------------------------------------------------
    def on_depth_updated(self, payload: Dict[str, Any]) -> None:
        symbol = payload.get("symbol")
        if self.symbol is not None and symbol != self.symbol:
            return
        depth = payload.get("depth") or {}
        bids = depth.get("bids", []) or []
        asks = depth.get("asks", []) or []

        st = self._state_for(symbol)
        now = time.time()

        cur_bids = {lvl.get("id"): float(lvl.get("size", 0.0)) for lvl in bids}
        cur_asks = {lvl.get("id"): float(lvl.get("size", 0.0)) for lvl in asks}
        st["book_total_bid"] = sum(cur_bids.values())
        st["book_total_ask"] = sum(cur_asks.values())

        # Whales: large resting orders on either side
        whales: Dict[Any, Dict[str, Any]] = {}
        for lvl in bids + asks:
            size = float(lvl.get("size", 0.0))
            if size >= self.whale_size:
                side = "bid" if lvl in bids else "ask"
                whales[lvl.get("id")] = {
                    "id": lvl.get("id"),
                    "side": side,
                    "price": lvl.get("price"),
                    "size": size,
                }
        st["whales"] = whales

        # Spoofing: previously-large orders that vanished with no fill
        prev_large_ids = {
            qid
            for qid, sz in {**st["prev_bids"], **st["prev_asks"]}.items()
            if sz >= self.spoof_size
        }
        cur_ids = set(cur_bids) | set(cur_asks)
        pulled = prev_large_ids - cur_ids
        spoofing = None
        if pulled:
            spoofing = self._detect_spoofing(st, pulled, now)

        st["prev_bids"] = cur_bids
        st["prev_asks"] = cur_asks

        self._publish(symbol, whales, None, spoofing, None)

    def _detect_spoofing(self, st: Dict[str, Any], pulled_ids: set, now: float) -> Optional[Dict[str, Any]]:
        # A spoof is a large order pulled within the spoof window with little
        # or no trade volume at its price during that window.
        recent = [t for t in st["trades"] if (now - t["ts"]) <= self.spoof_window]
        pulled_detail = []
        for qid in pulled_ids:
            # We only kept sizes in prev maps, not price; approximate as "pulled".
            pulled_detail.append({"id": qid})
        if not pulled_detail:
            return None
        # If there was meaningful trade volume in the window, it's less likely a
        # pure spoof (could be a genuine fill). Flag only when window is quiet.
        window_volume = sum(t["volume"] for t in recent)
        if window_volume >= self.spoof_size:
            return None
        return {"pulled": pulled_detail, "window": self.spoof_window}

    # ------------------------------------------------------------------
    # Trades -> absorption + divergence
    # ------------------------------------------------------------------
    def on_trade_executed(self, payload: Dict[str, Any]) -> None:
        symbol = payload.get("symbol")
        if self.symbol is not None and symbol != self.symbol:
            return
        st = self._state_for(symbol)
        now = time.time()

        is_buy = _side_is_buy(payload.get("side"))
        trade = {
            "price": payload.get("price"),
            "volume": float(payload.get("volume") or 0.0),
            "is_buy": is_buy,
            "ts": now,
        }
        st["trades"].append(trade)

        absorption = self._detect_absorption(st, trade)
        divergence = self._detect_divergence(st, trade, now)

        self._publish(symbol, st.get("whales", {}), absorption, None, divergence)

    def _detect_absorption(self, st: Dict[str, Any], trade: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        if trade["volume"] < self.absorption_volume:
            return None
        # A BUY trade hits the ask; absorption means ask-side liquidity held.
        # A SELL trade hits the bid; absorption means bid-side liquidity held.
        if trade["is_buy"] is None:
            return None
        side_total = st["book_total_ask"] if trade["is_buy"] else st["book_total_bid"]
        if side_total <= 0:
            return None
        # Liquidity present on the hit side at least as large as the trade =>
        # the book absorbed the market order instead of moving the price.
        if side_total >= trade["volume"]:
            direction = "ask" if trade["is_buy"] else "bid"
            return {
                "direction": direction,
                "volume": trade["volume"],
                "side_total": side_total,
            }
        return None

    def _detect_divergence(self, st: Dict[str, Any], trade: Dict[str, Any], now: float) -> Optional[Dict[str, Any]]:
        window_trades = [t for t in st["trades"] if (now - t["ts"]) <= self.trade_window]
        if len(window_trades) < 3:
            return None
        buy_vol = sum(t["volume"] for t in window_trades if t["is_buy"])
        sell_vol = sum(t["volume"] for t in window_trades if not t["is_buy"] and t["is_buy"] is not None)
        net_flow = buy_vol - sell_vol
        if net_flow == 0:
            return None
        # Price direction over the window
        prices = [t["price"] for t in window_trades if t["price"] is not None]
        if len(prices) < 2:
            return None
        price_delta = prices[-1] - prices[0]
        if price_delta == 0:
            return None
        # Divergence: flow and price disagree in direction.
        # Convention: price up while flow is net selling = bearish divergence;
        # price down while flow is net buying = bullish divergence.
        if (net_flow > 0 and price_delta < 0) or (net_flow < 0 and price_delta > 0):
            return {
                "type": "bearish" if (net_flow < 0 and price_delta > 0) else "bullish",
                "net_flow": net_flow,
                "price_delta": price_delta,
            }
        return None

    # ------------------------------------------------------------------
    # Publish
    # ------------------------------------------------------------------
    def _publish(
        self,
        symbol: str,
        whales: Dict[Any, Dict[str, Any]],
        absorption: Optional[Dict[str, Any]],
        spoofing: Optional[Dict[str, Any]],
        divergence: Optional[Dict[str, Any]],
    ) -> None:
        signal = {
            "symbol": symbol,
            "whales": list(whales.values()),
            "absorption": absorption,
            "spoofing": spoofing,
            "divergence": divergence,
            "timestamp": time.time(),
        }
        self._latest[symbol] = signal
        if self.event_bus is not None:
            self.event_bus.publish("OrderFlowSignal", signal)

    def get_latest(self, symbol: str) -> Optional[Dict[str, Any]]:
        return self._latest.get(symbol)
