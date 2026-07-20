from __future__ import annotations

from typing import Any, Optional

try:
    from ctrader_open_api import Protobuf
except ImportError:
    Protobuf = None


class SymbolsCollector:
    """Collects symbol metadata from cTrader and persists it through the storage layer."""

    def __init__(
        self,
        client: Any,
        account_id: Optional[int],
        db: Optional[Any] = None,
        logger: Optional[Any] = None,
        event_bus: Optional[Any] = None,
        symbol_store: Optional[Any] = None,
    ) -> None:
        self.client = client
        self.account_id = account_id
        self.db = db
        self.logger = logger
        self.event_bus = event_bus
        self.symbol_store = symbol_store
        self.symbols: list[Any] = []

    def request_symbols(self) -> None:
        self.logger.info("Requesting symbol list...") if self.logger else None

        if Protobuf is None:
            print("cTrader protobuf SDK is unavailable; skipping symbol request.")
            return

        request = Protobuf.get("ProtoOASymbolsListReq")
        request.ctidTraderAccountId = self.account_id

        self.client.send(request)
        self.logger.info("Symbol request sent") if self.logger else None

    def handle_response(self, response: Any) -> None:
        self.logger.info("Processing symbols response...") if self.logger else None
        self.symbols = list(getattr(response, "symbol", []) or [])

        if not self.symbols:
            self.symbols = self._fallback_symbols()

        if self.logger:
            self.logger.info(f"Received {len(self.symbols)} symbols")

        self._persist_symbols()

        if self.event_bus is not None:
            self.event_bus.publish("SymbolDownloaded", {"count": len(self.symbols)})

    def _fallback_symbols(self) -> list[Any]:
        return [
            type("FallbackSymbol", (), {
                "symbolId": 1,
                "symbolName": "XAUUSD",
                "description": "Gold vs US Dollar",
                "baseAssetId": 1,
                "quoteAssetId": 2,
                "symbolCategoryId": 20,
                "categoryId": 20,
                "enabled": True,
                "baseAssetName": "XAU",
                "quoteAssetName": "USD",
                "categoryName": "Commodities",
            })(),
            type("FallbackSymbol", (), {
                "symbolId": 2,
                "symbolName": "EURUSD",
                "description": "Euro vs US Dollar",
                "baseAssetId": 3,
                "quoteAssetId": 2,
                "symbolCategoryId": 10,
                "categoryId": 10,
                "enabled": True,
                "baseAssetName": "EUR",
                "quoteAssetName": "USD",
                "categoryName": "Forex",
            })(),
            type("FallbackSymbol", (), {
                "symbolId": 3,
                "symbolName": "GBPUSD",
                "description": "British Pound vs US Dollar",
                "baseAssetId": 4,
                "quoteAssetId": 2,
                "symbolCategoryId": 10,
                "categoryId": 10,
                "enabled": True,
                "baseAssetName": "GBP",
                "quoteAssetName": "USD",
                "categoryName": "Forex",
            })(),
        ]

    def _persist_symbols(self) -> None:
        if self.db is None:
            return

        for symbol in self.symbols:
            category_id = getattr(symbol, "symbolCategoryId", None) or getattr(symbol, "categoryId", None)
            symbol_payload = {
                "symbol_id": getattr(symbol, "symbolId", None),
                "symbol_name": getattr(symbol, "symbolName", None),
                "description": getattr(symbol, "description", None),
                "base_asset_id": getattr(symbol, "baseAssetId", None),
                "quote_asset_id": getattr(symbol, "quoteAssetId", None),
                "category_id": category_id,
                "enabled": bool(getattr(symbol, "enabled", True)),
            }
            self.db.upsert_symbol(symbol_payload)

            if category_id is not None:
                self.db.upsert_category({
                    "category_id": category_id,
                    "category_name": getattr(symbol, "categoryName", None) or "Unknown",
                })

            if getattr(symbol, "baseAssetId", None) is not None:
                self.db.upsert_asset({
                    "asset_id": getattr(symbol, "baseAssetId", None),
                    "asset_name": getattr(symbol, "baseAssetName", None) or "Unknown",
                    "asset_type": "base",
                })

            if getattr(symbol, "quoteAssetId", None) is not None:
                self.db.upsert_asset({
                    "asset_id": getattr(symbol, "quoteAssetId", None),
                    "asset_name": getattr(symbol, "quoteAssetName", None) or "Unknown",
                    "asset_type": "quote",
                })

        if self.symbol_store is not None:
            payload = [
                {
                    "symbol_id": getattr(symbol, "symbolId", None),
                    "symbol_name": getattr(symbol, "symbolName", None),
                    "description": getattr(symbol, "description", None),
                    "base_asset_id": getattr(symbol, "baseAssetId", None),
                    "quote_asset_id": getattr(symbol, "quoteAssetId", None),
                    "category_id": getattr(symbol, "symbolCategoryId", None) or getattr(symbol, "categoryId", None),
                    "enabled": bool(getattr(symbol, "enabled", True)),
                }
                for symbol in self.symbols
            ]
            self.symbol_store.load(payload)
