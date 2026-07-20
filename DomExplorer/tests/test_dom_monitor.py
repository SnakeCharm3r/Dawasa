import logging
import unittest
import io
from types import SimpleNamespace

from src.services.event_bus import EventBus
from src.collectors.market_depth import MarketDepthCollector
from src.utils.console import DomMonitor


class DomMonitorTests(unittest.TestCase):
    def _monitor(self, **kwargs):
        return DomMonitor(event_bus=EventBus(), symbol="XAUUSD", refresh_interval=0.05, **kwargs)

    def test_monitor_subscribes_and_updates_snapshot(self):
        monitor = self._monitor()
        collector = MarketDepthCollector(event_bus=monitor.event_bus)
        collector.apply_event(
            "XAUUSD",
            [
                {"id": 1, "size": 10, "bid": 2000.0},
                {"id": 2, "size": 5, "ask": 2001.0},
            ],
            [],
        )

        depth = monitor._snapshot
        self.assertIsNotNone(depth)
        self.assertEqual(len(depth["bids"]), 1)
        self.assertEqual(len(depth["asks"]), 1)

        metrics = monitor._latest_metrics
        self.assertAlmostEqual(metrics["bid_volume"], 10.0)
        self.assertAlmostEqual(metrics["ask_volume"], 5.0)
        self.assertAlmostEqual(metrics["spread"], 1.0)
        self.assertAlmostEqual(metrics["best_bid"], 2000.0)
        self.assertAlmostEqual(metrics["best_ask"], 2001.0)
        # Imbalance now comes from the existing analyzer (price based), not recalc.
        self.assertAlmostEqual(metrics["imbalance"], (2000.0 - 2001.0) / 2001.0, places=6)

        table = monitor._render()
        self.assertEqual(table.title, "DOM Monitor — XAUUSD")

    def test_monitor_ignores_other_symbols(self):
        monitor = self._monitor()
        monitor.event_bus.publish(
            "DepthUpdated",
            {"symbol": "EURUSD", "depth": {"bids": [{"price": 1.1, "size": 1}], "asks": []}, "top_of_book": {}},
        )
        self.assertIsNone(monitor._snapshot)

    def test_price_normalization_uses_digits(self):
        monitor = self._monitor(digits_by_symbol={"XAUUSD": 2})
        # raw integer prices scaled by 10**2
        norm = monitor.normalize_price(200075, "XAUUSD")
        self.assertAlmostEqual(norm, 2000.75, places=4)

        monitor_zero = self._monitor()  # digits default 0 -> no scaling
        self.assertAlmostEqual(monitor_zero.normalize_price(2000.0, "XAUUSD"), 2000.0)

    def test_invalid_levels_filtered_and_deduped(self):
        monitor = self._monitor()
        raw = [
            {"id": 1, "price": 0, "size": 10},        # invalid price (0)
            {"id": 2, "price": 2000, "size": -5},      # invalid size
            {"id": 3, "price": 2001, "size": 0},       # non-positive size
            {"id": 4, "price": 2002, "size": 7},
            {"id": 4, "price": 2002, "size": 9},       # duplicate id -> kept once (latest)
            {"id": 5, "price": None, "size": 3},       # missing price
        ]
        bids = monitor._build_levels(raw, "bids")
        self.assertEqual(len(bids), 1)
        self.assertEqual(bids[0]["id"], 4)
        self.assertEqual(bids[0]["size"], 9)

    def test_asks_sorted_ascending_bids_descending(self):
        monitor = self._monitor()
        asks = monitor._build_levels(
            [{"id": 1, "price": 2003, "size": 1}, {"id": 2, "price": 2001, "size": 1}, {"id": 3, "price": 2002, "size": 1}],
            "asks",
        )
        self.assertEqual([l["price"] for l in asks], [2001, 2002, 2003])

        bids = monitor._build_levels(
            [{"id": 1, "price": 2000, "size": 1}, {"id": 2, "price": 1998, "size": 1}, {"id": 3, "price": 1999, "size": 1}],
            "bids",
        )
        self.assertEqual([l["price"] for l in bids], [2000, 1999, 1998])

    def test_last_row_shows_fallback_when_no_execution(self):
        monitor = self._monitor()
        monitor.event_bus.publish(
            "DepthUpdated",
            {"symbol": "XAUUSD", "depth": {"bids": [{"price": 2000.0, "size": 1}], "asks": [{"price": 2001.0, "size": 1}]}, "top_of_book": {}},
        )
        out = io.StringIO()
        from rich.console import Console
        Console(file=out, width=90).print(monitor._render())
        self.assertIn("No execution data available", out.getvalue())

    def test_malformed_depth_does_not_crash_render(self):
        monitor = self._monitor()
        # snapshot with non-list/garbage levels
        monitor._snapshot = {"bids": "not-a-list", "asks": None}
        try:
            table = monitor._render()
            self.assertIsNotNone(table)
        except Exception as exc:  # pragma: no cover
            self.fail(f"Render crashed on malformed data: {exc}")

    def test_debug_logging_emitted_on_normalization(self):
        logs = io.StringIO()
        handler = logging.StreamHandler(logs)
        logger = logging.getLogger("DomMonitor")
        logger.setLevel(logging.DEBUG)
        logger.addHandler(handler)

        monitor = self._monitor(digits_by_symbol={"XAUUSD": 2}, logger=logger)
        monitor.normalize_price(200075, "XAUUSD")
        self.assertIn("Normalized", logs.getvalue())


if __name__ == "__main__":
    unittest.main()
