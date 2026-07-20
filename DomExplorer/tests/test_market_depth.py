import unittest

from src.collectors.market_depth import MarketDepthCollector


class MarketDepthCollectorTests(unittest.TestCase):
    def test_apply_event_builds_sorted_book(self):
        collector = MarketDepthCollector(max_levels=3)

        collector.apply_event(
            "XAUUSD",
            [
                {"id": 1, "size": 10, "ask": 2001.5},
                {"id": 2, "size": 5, "bid": 2000.0},
                {"id": 3, "size": 8, "ask": 2002.0},
                {"id": 4, "size": 12, "bid": 1999.5},
            ],
            [],
        )

        depth = collector.get("XAUUSD")
        self.assertEqual([b["price"] for b in depth["bids"]], [2000.0, 1999.5])
        self.assertEqual([a["price"] for a in depth["asks"]], [2001.5, 2002.0])

        top = collector.top_of_book("XAUUSD")
        self.assertEqual(top["bid"], 2000.0)
        self.assertEqual(top["ask"], 2001.5)

    def test_deleted_quotes_are_removed(self):
        collector = MarketDepthCollector()
        collector.apply_event("XAUUSD", [{"id": 1, "size": 10, "bid": 2000.0}], [])
        collector.apply_event("XAUUSD", [], [1])

        depth = collector.get("XAUUSD")
        self.assertEqual(depth["bids"], [])

    def test_max_levels_is_respected(self):
        collector = MarketDepthCollector(max_levels=2)
        collector.apply_event(
            "XAUUSD",
            [
                {"id": 1, "size": 1, "bid": 2000.0},
                {"id": 2, "size": 1, "bid": 1999.0},
                {"id": 3, "size": 1, "bid": 1998.0},
            ],
            [],
        )
        self.assertEqual(len(collector.get("XAUUSD")["bids"]), 2)


if __name__ == "__main__":
    unittest.main()
