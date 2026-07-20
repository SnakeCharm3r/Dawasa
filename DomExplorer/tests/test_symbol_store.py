import unittest

from src.storage.symbol_store import SymbolStore


class SymbolStoreTests(unittest.TestCase):
    def test_load_and_lookup_symbols(self):
        store = SymbolStore()
        items = [
            {
                "symbol_id": 1,
                "symbol_name": "EURUSD",
                "description": "Euro vs Dollar",
                "enabled": True,
                "category_id": 10,
                "base_asset_id": 1,
                "quote_asset_id": 2,
            },
            {
                "symbol_id": 2,
                "symbol_name": "GBPUSD",
                "description": "Pound vs Dollar",
                "enabled": False,
                "category_id": 10,
                "base_asset_id": 3,
                "quote_asset_id": 2,
            },
        ]

        store.load(items)

        self.assertEqual(store.count(), 2)
        self.assertTrue(store.exists("EURUSD"))
        self.assertEqual(store.get("EURUSD")["symbol_id"], 1)
        self.assertEqual(store.get_by_id(2)["symbol_name"], "GBPUSD")
        self.assertEqual(len(store.enabled()), 1)
        self.assertEqual(len(store.disabled()), 1)

    def test_search_symbols_by_name(self):
        store = SymbolStore()
        store.load([
            {"symbol_id": 1, "symbol_name": "EURUSD", "description": "", "enabled": True, "category_id": 1, "base_asset_id": 1, "quote_asset_id": 2},
            {"symbol_id": 2, "symbol_name": "EURJPY", "description": "", "enabled": True, "category_id": 1, "base_asset_id": 1, "quote_asset_id": 3},
        ])

        matches = store.search("EUR")
        self.assertEqual(len(matches), 2)
        self.assertTrue(all("EUR" in item["symbol_name"] for item in matches))
