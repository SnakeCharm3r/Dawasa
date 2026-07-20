from __future__ import annotations

from typing import Any, Dict, List, Optional


class ExecutionCollector:
    """Tracks executed trades and publishes them on the EventBus.

    cTrader delivers fills through ``ProtoOAExecutionEvent`` which carries one
    or more ``ProtoOADeal`` entries. Each deal has the traded ``symbolId``,
    ``executionPrice`` and ``filledVolume``. This collector keeps the latest
    traded price per symbol and emits a ``TradeExecuted`` event so downstream
    consumers (DOM monitor, analysis) stay decoupled from the raw feed.
    """

    def __init__(self, event_bus: Optional[Any] = None, symbol_store: Optional[Any] = None) -> None:
        self._event_bus = event_bus
        self._symbol_store = symbol_store
        self._last_price: Dict[str, Dict[str, Any]] = {}

    def _resolve_symbol(self, symbol_id: Any) -> Optional[str]:
        if symbol_id is None or self._symbol_store is None:
            return None
        sym = self._symbol_store.get_by_id(symbol_id)
        return sym.get("symbol_name") if sym else None

    def handle_execution_event(self, decoded: Any) -> None:
        deals = getattr(decoded, "deal", []) or []
        for deal in deals:
            symbol_id = getattr(deal, "symbolId", None)
            price = getattr(deal, "executionPrice", None)
            if price is None:
                continue
            symbol = self._resolve_symbol(symbol_id)
            if symbol is None:
                continue
            self._last_price[symbol] = {
                "symbol": symbol,
                "price": float(price),
                "volume": float(getattr(deal, "filledVolume", 0.0) or getattr(deal, "volume", 0.0)),
                "side": getattr(deal, "tradeSide", None),
                "timestamp": getattr(deal, "executionTimestamp", None)
                or getattr(deal, "createTimestamp", None),
            }
            if self._event_bus is not None:
                self._event_bus.publish("TradeExecuted", self._last_price[symbol])

    def get_last_price(self, symbol: str) -> Optional[Dict[str, Any]]:
        return self._last_price.get(symbol)

    def snapshot(self) -> Dict[str, Dict[str, Any]]:
        return dict(self._last_price)
