import io
import time
import unittest

from src.services.event_bus import EventBus
from src.analysis.order_flow import OrderFlowAnalyzer, _side_is_buy
from src.utils.console import DomMonitor
from rich.console import Console


class OrderFlowTests(unittest.TestCase):
    def test_side_normalization(self):
        self.assertTrue(_side_is_buy("BUY"))
        self.assertFalse(_side_is_buy("SELL"))
        self.assertTrue(_side_is_buy(1))
        self.assertFalse(_side_is_buy(2))
        self.assertIsNone(_side_is_buy(None))

    def test_whale_detected_and_published(self):
        eb = EventBus()
        ana = OrderFlowAnalyzer(eb, symbol="XAUUSD", whale_size=500)
        received = {}
        eb.subscribe("OrderFlowSignal", lambda p: received.update(p))

        eb.publish("DepthUpdated", {
            "symbol": "XAUUSD",
            "depth": {"bids": [{"id": 1, "price": 2000.0, "size": 10}, {"id": 99, "price": 1999.0, "size": 800}],
                     "asks": [{"id": 2, "price": 2001.0, "size": 5}]},
            "top_of_book": {},
        })
        self.assertEqual(len(received.get("whales", [])), 1)
        self.assertEqual(received["whales"][0]["id"], 99)

    def test_absorption_when_book_holds(self):
        eb = EventBus()
        ana = OrderFlowAnalyzer(eb, symbol="XAUUSD", absorption_volume=300, whale_size=1e9)
        signals = []
        eb.subscribe("OrderFlowSignal", signals.append)

        eb.publish("DepthUpdated", {
            "symbol": "XAUUSD",
            "depth": {"bids": [{"id": 1, "price": 2000.0, "size": 10}],
                     "asks": [{"id": 2, "price": 2001.0, "size": 5000}]},  # big ask liquidity
            "top_of_book": {},
        })
        eb.publish("TradeExecuted", {"symbol": "XAUUSD", "price": 2001.0, "volume": 400, "side": "BUY", "timestamp": time.time()})
        last = signals[-1]
        self.assertIsNotNone(last["absorption"])
        self.assertEqual(last["absorption"]["direction"], "ask")

    def test_spoofing_when_large_order_pulled(self):
        eb = EventBus()
        ana = OrderFlowAnalyzer(eb, symbol="XAUUSD", spoof_size=500, whale_size=1e9, spoof_window=5.0)
        signals = []
        eb.subscribe("OrderFlowSignal", signals.append)

        eb.publish("DepthUpdated", {
            "symbol": "XAUUSD",
            "depth": {"bids": [], "asks": [{"id": 42, "price": 2001.0, "size": 900}]},
            "top_of_book": {},
        })
        # next update: the large order is gone, no trades in window -> spoof
        eb.publish("DepthUpdated", {
            "symbol": "XAUUSD",
            "depth": {"bids": [], "asks": [{"id": 43, "price": 2002.0, "size": 5}]},
            "top_of_book": {},
        })
        last = signals[-1]
        self.assertIsNotNone(last["spoofing"])

    def test_divergence_detected(self):
        eb = EventBus()
        ana = OrderFlowAnalyzer(eb, symbol="XAUUSD", whale_size=1e9, trade_window=10.0)
        signals = []
        eb.subscribe("OrderFlowSignal", signals.append)

        # Price rising but aggressive selling -> bearish divergence
        for i, price in enumerate([2000.0, 2000.5, 2001.0]):
            eb.publish("TradeExecuted", {"symbol": "XAUUSD", "price": price, "volume": 100, "side": "SELL", "timestamp": time.time() + i})
        last = signals[-1]
        self.assertIsNotNone(last["divergence"])
        self.assertEqual(last["divergence"]["type"], "bearish")


class MonitorWhaleRenderTests(unittest.TestCase):
    def test_whale_rows_rendered_and_signals_printed(self):
        eb = EventBus()
        monitor = DomMonitor(event_bus=eb, symbol="XAUUSD", refresh_interval=0.05)

        # depth with a large whale order
        eb.publish("DepthUpdated", {
            "symbol": "XAUUSD",
            "depth": {"bids": [{"id": 1, "price": 2000.0, "size": 10}, {"id": 7, "price": 1999.0, "size": 800}],
                     "asks": [{"id": 2, "price": 2001.0, "size": 5}]},
            "top_of_book": {},
        })
        # order-flow signal declaring that whale
        eb.publish("OrderFlowSignal", {
            "symbol": "XAUUSD",
            "whales": [{"id": 7, "side": "bid", "price": 1999.0, "size": 800}],
            "absorption": {"direction": "ask", "volume": 400, "side_total": 5000},
            "spoofing": {"pulled": [{"id": 99}]},
            "divergence": {"type": "bearish", "net_flow": -300, "price_delta": 1.5},
            "timestamp": time.time(),
        })

        buf = io.StringIO()
        Console(file=buf, width=140).print(monitor._render())
        out = buf.getvalue()
        self.assertIn("ABSORPTION", out)
        self.assertIn("SPOOFING", out)
        self.assertIn("DIVERGENCE", out)


if __name__ == "__main__":
    unittest.main()
