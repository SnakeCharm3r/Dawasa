import os
import tempfile
import unittest
from types import SimpleNamespace

from src.services.event_bus import EventBus
from src.collectors.market_depth import MarketDepthCollector
from src.analysis.analysis_listener import AnalysisListener
from src.storage.symbol_store import SymbolStore
from src.storage.database import DatabaseManager


class DepthAnalysisPipelineTests(unittest.TestCase):
    def test_depth_update_publishes_event_and_persists_metrics(self):
        with tempfile.TemporaryDirectory() as tmp_dir:
            db_path = os.path.join(tmp_dir, "test.sqlite3")
            config = SimpleNamespace(
                DB_HOST="localhost",
                DB_PORT=3306,
                DB_USER="root",
                DB_PASSWORD="",
                DB_NAME="domexplorer",
                DB_PATH=db_path,
            )
            db = DatabaseManager(config)
            db.connect()

            event_bus = EventBus()
            symbol_store = SymbolStore()
            symbol_store.load([
                {"symbol_id": 1, "symbol_name": "XAUUSD", "description": "", "enabled": True,
                 "category_id": 20, "base_asset_id": 1, "quote_asset_id": 2},
            ])

            collector = MarketDepthCollector(event_bus=event_bus)
            listener = AnalysisListener(
                event_bus=event_bus, db=db, symbol_store=symbol_store
            )

            collector.apply_event(
                "XAUUSD",
                [
                    {"id": 1, "size": 10, "bid": 2000.0},
                    {"id": 2, "size": 5, "ask": 2001.0},
                ],
                [],
            )

            rows = db.fetchall(
                "SELECT symbol_id, metric_name, metric_value FROM metrics_history ORDER BY metric_name"
            )

            self.assertGreaterEqual(len(rows), 9)
            metric_names = {row["metric_name"] for row in rows}
            for expected in [
                "imbalance", "pressure", "liquidity", "volatility", "spread",
                "dom_bid_volume", "dom_ask_volume", "dom_imbalance", "dom_spread",
            ]:
                self.assertIn(expected, metric_names)

            for row in rows:
                self.assertEqual(row["symbol_id"], 1)


if __name__ == "__main__":
    unittest.main()
