from __future__ import annotations

from collections import defaultdict, deque
from typing import Any, Callable, Deque, Dict, List, Optional


class MarketDepthCollector:
    """Stores incremental DOM (depth of market) updates for subscribed symbols.

    cTrader delivers depth through ``ProtoOADepthEvent`` messages. Each event
    carries a list of ``newQuotes`` (``ProtoOADepthQuote`` with id, size, optional
    bid/ask price) and a list of ``deletedQuotes`` (quote ids to remove). This
    collector keeps the full per-symbol book state and rebuilds a sorted
    top-of-book snapshot on every update.

    A ``DepthUpdated`` event is published on the supplied ``EventBus`` whenever a
    symbol book changes, keeping the collector decoupled from downstream analysis.
    """

    def __init__(
        self,
        max_levels: int = 10,
        history_size: int = 100,
        event_bus: Optional[Any] = None,
    ) -> None:
        self.max_levels = max_levels
        self._history_size = history_size
        self._event_bus = event_bus
        self._state: Dict[str, Dict[str, Dict[int, Dict[str, Any]]]] = defaultdict(
            lambda: {"bids": {}, "asks": {}}
        )
        self._snapshots: Dict[str, Deque[Dict[str, Any]]] = defaultdict(
            lambda: deque(maxlen=self._history_size)
        )

    def apply_event(
        self,
        symbol: str,
        new_quotes: List[Dict[str, Any]],
        deleted_ids: List[int],
    ) -> None:
        state = self._state[symbol]
        for quote in new_quotes:
            qid = quote.get("id")
            if qid is None:
                continue
            bid = quote.get("bid")
            ask = quote.get("ask")
            size = float(quote.get("size", 0))
            # cTrader depth quotes are strictly one-sided: the inactive side is
            # delivered as 0 (the protobuf default), not as None. Treat 0 and
            # None identically as "side not present" so a bid quote is never
            # also stored as a 0.0-priced ask (and vice versa). A quote with no
            # usable (non-zero) price on either side is a degenerate cumulative
            # level and is dropped.
            has_bid = bid is not None and bid != 0
            has_ask = ask is not None and ask != 0
            if not has_bid and not has_ask:
                continue
            if has_bid:
                state["bids"][qid] = {"id": qid, "price": float(bid), "size": size}
            if has_ask:
                state["asks"][qid] = {"id": qid, "price": float(ask), "size": size}
        for qid in deleted_ids:
            state["bids"].pop(qid, None)
            state["asks"].pop(qid, None)
        self._snapshot(symbol)
        self._publish(symbol)

    def update(self, symbol: str, levels: List[Dict[str, Any]]) -> None:
        """Full replacement update (used when rebuilding from a snapshot)."""
        state = self._state[symbol]
        state["bids"].clear()
        state["asks"].clear()
        self.apply_event(symbol, levels, [])
        self._snapshot(symbol)
        self._publish(symbol)

    def _publish(self, symbol: str) -> None:
        if self._event_bus is None:
            return
        snapshot = self.get(symbol)
        self._event_bus.publish(
            "DepthUpdated",
            {"symbol": symbol, "depth": snapshot, "top_of_book": self.top_of_book(symbol)},
        )

    def _snapshot(self, symbol: str) -> None:
        state = self._state[symbol]
        bids = sorted(state["bids"].values(), key=lambda x: x["price"], reverse=True)[: self.max_levels]
        asks = sorted(state["asks"].values(), key=lambda x: x["price"])[: self.max_levels]
        self._snapshots[symbol].append({"symbol": symbol, "bids": bids, "asks": asks})

    def get(self, symbol: str) -> Optional[Dict[str, Any]]:
        state = self._state.get(symbol)
        if not state:
            return None
        bids = sorted(state["bids"].values(), key=lambda x: x["price"], reverse=True)[: self.max_levels]
        asks = sorted(state["asks"].values(), key=lambda x: x["price"])[: self.max_levels]
        return {"symbol": symbol, "bids": bids, "asks": asks}

    def snapshot(self) -> Dict[str, Dict[str, Any]]:
        result: Dict[str, Dict[str, Any]] = {}
        for symbol in self._state:
            result[symbol] = self.get(symbol)
        return result

    def history(self, symbol: str) -> Deque[Dict[str, Any]]:
        return self._snapshots.get(symbol, deque(maxlen=self._history_size))

    def top_of_book(self, symbol: str) -> Optional[Dict[str, float]]:
        depth = self.get(symbol)
        if not depth:
            return None
        best_bid = depth["bids"][0]["price"] if depth["bids"] else None
        best_ask = depth["asks"][0]["price"] if depth["asks"] else None
        return {"bid": best_bid, "ask": best_ask}

    def clear(self) -> None:
        self._state.clear()
        self._snapshots.clear()
