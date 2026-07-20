import unittest
from types import SimpleNamespace

from src.services.event_bus import EventBus
from src.collectors.execution import ExecutionCollector
from src.utils.console import DomMonitor
from src.storage.symbol_store import SymbolStore


class ExecutionFeedTests(unittest.TestCase):
    def test_execution_event_publishes_last_price(self):
        event_bus = EventBus()
        symbol_store = SymbolStore()
        symbol_store.load([
            {"symbol_id": 1, "symbol_name": "XAUUSD", "description": "", "enabled": True,
             "category_id": 20, "base_asset_id": 1, "quote_asset_id": 2},
        ])
        collector = ExecutionCollector(event_bus=event_bus, symbol_store=symbol_store)

        class FakeDeal:
            symbolId = 1
            executionPrice = 2000.5
            filledVolume = 3
            tradeSide = "BUY"
            executionTimestamp = 123

        class FakeExecEvent:
            deal = [FakeDeal()]

        collector.handle_execution_event(FakeExecEvent())

        last = collector.get_last_price("XAUUSD")
        self.assertIsNotNone(last)
        self.assertEqual(last["price"], 2000.5)
        self.assertEqual(last["volume"], 3)

    def test_monitor_shows_last_price_between_depth(self):
        event_bus = EventBus()
        monitor = DomMonitor(event_bus=event_bus, symbol="XAUUSD", refresh_interval=0.05)

        # depth
        event_bus.publish("DepthUpdated", {
            "symbol": "XAUUSD",
            "depth": {
                "symbol": "XAUUSD",
                "bids": [{"price": 2000.0, "size": 10}],
                "asks": [{"price": 2001.0, "size": 5}],
            },
            "top_of_book": {"bid": 2000.0, "ask": 2001.0},
        })
        # trade
        event_bus.publish("TradeExecuted", {
            "symbol": "XAUUSD", "price": 2000.75, "volume": 2, "side": "BUY", "timestamp": 1,
        })

        table = monitor._render()
        from rich.console import Console
        from io import StringIO
        buf = StringIO()
        Console(file=buf, width=80).print(table)
        rendered = buf.getvalue()
        self.assertIn("LAST", rendered)
        self.assertIn("2,000.75", rendered)
        self.assertIn("ASK", rendered)
        self.assertIn("BID", rendered)
        self.assertIsNotNone(monitor._last_trade)
        self.assertEqual(monitor._last_trade["price"], 2000.75)


if __name__ == "__main__":
    unittest.main()
