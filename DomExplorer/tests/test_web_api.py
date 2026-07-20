import unittest
from types import SimpleNamespace

from src.utils.price import PriceNormalizer, build_normalizer_from_config
from src.config import config


class PriceNormalizerTests(unittest.TestCase):
    def test_no_digits_passthrough(self):
        n = PriceNormalizer()
        self.assertEqual(n.normalize(2000.0, "XAUUSD"), 2000.0)

    def test_scales_by_digits(self):
        n = PriceNormalizer({"XAUUSD": 2})
        self.assertAlmostEqual(n.normalize(200075, "XAUUSD"), 2000.75)
        n2 = PriceNormalizer({"GBPUSD": 5})
        self.assertAlmostEqual(n2.normalize(125003000, "GBPUSD"), 1250.03)

    def test_missing_raw_returns_none(self):
        n = PriceNormalizer({"XAUUSD": 2})
        self.assertIsNone(n.normalize(None, "XAUUSD"))
        self.assertIsNone(n.normalize("not-a-number", "XAUUSD"))

    def test_build_normalizer_from_config(self):
        cfg = SimpleNamespace(FOCUS_SYMBOL="XAUUSD", FOCUS_SYMBOL_DIGITS=2, SYMBOL_DIGITS="XAUUSD:2,GBPUSD:5")
        n = build_normalizer_from_config(cfg, "XAUUSD")
        self.assertAlmostEqual(n.normalize(200075, "XAUUSD"), 2000.75)
        self.assertAlmostEqual(n.normalize(125003000, "GBPUSD"), 1250.03)


class WebApiTests(unittest.TestCase):
    def _app(self):
        from src.web import create_app
        from src.storage.database import DatabaseManager
        import os, tempfile

        db_path = os.path.join(tempfile.mkdtemp(), "web.sqlite3")
        cfg = SimpleNamespace(
            DB_HOST="h", DB_PORT=3306, DB_USER="r", DB_PASSWORD="", DB_NAME="d", DB_PATH=db_path
        )
        db = DatabaseManager(cfg)
        db.connect()
        db.upsert_symbol({"symbol_id": 41, "symbol_name": "XAUUSD", "enabled": True})
        # depth is stored RAW (integer-scaled, XAUUSD digits=2): 2000.5 -> 200050
        db.replace_depth_snapshots(41, [
            {"side": "bid", "quote_id": 1, "price": 200050, "size": 800},
            {"side": "ask", "quote_id": 2, "price": 200100, "size": 5},
        ])
        db.upsert_metric({"symbol_id": 41, "metric_name": "spread", "metric_value": 0.5})
        db.upsert_metric({"symbol_id": 41, "metric_name": "dom_bid_volume", "metric_value": 800.0})
        return create_app(db)

    def test_health_and_symbols(self):
        c = self._app().test_client()
        self.assertEqual(c.get("/api/health").get_json()["status"], "ok")
        self.assertEqual(len(c.get("/api/symbols").get_json()), 1)

    def test_depth_normalized_and_latest(self):
        c = self._app().test_client()
        depth = c.get("/api/symbols/XAUUSD/depth").get_json()
        # API normalizes raw stored prices back to real-world values
        self.assertAlmostEqual(depth["bids"][0]["price"], 2000.5)
        self.assertEqual(depth["asks"][0]["size"], 5)
        latest = c.get("/api/symbols/XAUUSD/latest").get_json()
        # normalized spread is a sane small number, not a raw integer garbage value
        self.assertAlmostEqual(latest["metrics"]["spread"], 0.5)


if __name__ == "__main__":
    unittest.main()
