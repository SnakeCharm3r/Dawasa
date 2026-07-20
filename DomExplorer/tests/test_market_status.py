import unittest
from datetime import datetime

from src.services.market_status import MarketStatusService
from src.config import config


class MarketStatusTests(unittest.TestCase):
    def test_weekend_is_closed_for_forex(self):
        svc = MarketStatusService()
        sat = datetime(2026, 7, 18, 12, 0)
        sun = datetime(2026, 7, 19, 12, 0)
        self.assertTrue(svc.is_weekend(sat))
        self.assertTrue(svc.is_weekend(sun))
        self.assertFalse(svc.is_symbol_open("XAUUSD", sat))
        self.assertFalse(svc.is_symbol_open("GBPUSD", sun))

    def test_weekday_forex_open(self):
        svc = MarketStatusService()
        wed = datetime(2026, 7, 22, 14, 0)
        self.assertFalse(svc.is_weekend(wed))
        self.assertTrue(svc.is_symbol_open("XAUUSD", wed))

    def test_crypto_open_on_weekend(self):
        svc = MarketStatusService()
        sat = datetime(2026, 7, 18, 12, 0)
        self.assertIn("BTCUSD", config.MARKET_ALWAYS_OPEN_SYMBOLS)
        self.assertTrue(svc.is_symbol_open("BTCUSD", sat))

    def test_markets_closed_weekend_for_traditional(self):
        svc = MarketStatusService()
        sat = datetime(2026, 7, 18, 12, 0)
        # Traditional market closed on weekend (even when BTCUSD is also watched).
        self.assertTrue(svc.markets_closed(["XAUUSD", "GBPUSD"], sat))
        self.assertTrue(svc.markets_closed(["XAUUSD", "BTCUSD"], sat))
        # Crypto-only watch list is never "closed".
        self.assertFalse(svc.markets_closed(["BTCUSD"], sat))

    def test_check_emits_event_on_transition(self):
        events = []
        svc = MarketStatusService(event_bus=_FakeBus(events))
        svc._last_state = None
        svc.check(["XAUUSD"], datetime(2026, 7, 22, 14, 0))  # open -> emits
        self.assertEqual(len(events), 1)
        svc.check(["XAUUSD"], datetime(2026, 7, 22, 14, 0))  # same state -> no re-emit
        self.assertEqual(len(events), 1)


class _FakeBus:
    def __init__(self, sink):
        self.sink = sink

    def publish(self, name, payload):
        self.sink.append((name, payload))


if __name__ == "__main__":
    unittest.main()
