from __future__ import annotations

from typing import Any, Dict, List, Optional


class SymbolStore:
    """In-memory symbol registry optimized for O(1) lookups."""

    def __init__(self) -> None:
        self.by_id: Dict[int, dict] = {}
        self.by_name: Dict[str, dict] = {}

    def load(self, symbols: List[Dict[str, Any]]) -> None:
        self.by_id = {}
        self.by_name = {}
        for symbol in symbols:
            self._store(symbol)

    def reload(self, symbols: Optional[List[Dict[str, Any]]] = None) -> None:
        if symbols is None:
            symbols = []
        self.load(symbols)

    def _store(self, symbol: Dict[str, Any]) -> None:
        if not symbol:
            return
        symbol_id = symbol.get("symbol_id")
        if symbol_id is None:
            return
        self.by_id[symbol_id] = symbol
        name = str(symbol.get("symbol_name", "")).strip()
        if name:
            self.by_name[name.upper()] = symbol

    def get(self, symbol_name: str) -> Optional[Dict[str, Any]]:
        return self.by_name.get(symbol_name.upper())

    def get_by_id(self, symbol_id: int) -> Optional[Dict[str, Any]]:
        return self.by_id.get(symbol_id)

    def exists(self, symbol_name: str) -> bool:
        return self.get(symbol_name) is not None

    def count(self) -> int:
        return len(self.by_id)

    def all(self) -> List[Dict[str, Any]]:
        return list(self.by_id.values())

    def enabled(self) -> List[Dict[str, Any]]:
        return [item for item in self.all() if item.get("enabled")]

    def disabled(self) -> List[Dict[str, Any]]:
        return [item for item in self.all() if not item.get("enabled")]

    def search(self, query: str) -> List[Dict[str, Any]]:
        needle = query.upper()
        return [item for item in self.all() if needle in str(item.get("symbol_name", "")).upper()]

    def category(self, category_id: int) -> List[Dict[str, Any]]:
        return [item for item in self.all() if item.get("category_id") == category_id]

    def asset(self, asset_id: int) -> List[Dict[str, Any]]:
        return [item for item in self.all() if item.get("base_asset_id") == asset_id or item.get("quote_asset_id") == asset_id]
