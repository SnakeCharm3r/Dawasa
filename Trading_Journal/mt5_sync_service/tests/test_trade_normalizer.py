from __future__ import annotations

import unittest

from mt5_sync_service.trade_normalizer import normalize_deals, reconstruct_closed_positions


CONSTANTS = {
    "DEAL_ENTRY_IN": 0,
    "DEAL_ENTRY_OUT": 1,
    "DEAL_ENTRY_INOUT": 2,
    "DEAL_ENTRY_OUT_BY": 3,
    "DEAL_TYPE_BUY": 0,
    "DEAL_TYPE_SELL": 1,
}


def deal(ticket: int, position: int, entry: int, side: int, volume: float, price: float, **extra):
    return {
        "ticket": ticket,
        "order": ticket + 1000,
        "position_id": position,
        "entry": entry,
        "type": side,
        "symbol": "XAUUSD",
        "time": 1_700_000_000 + ticket,
        "price": price,
        "volume": volume,
        "profit": extra.get("profit", 0),
        "commission": extra.get("commission", -1),
        "swap": extra.get("swap", 0),
        "fee": extra.get("fee", 0),
        "magic": 42,
        "comment": extra.get("comment", "test"),
        "sl": extra.get("sl", 0),
        "tp": extra.get("tp", 0),
    }


class TradeNormalizerTests(unittest.TestCase):
    def test_reconstructs_buy_position(self):
        normalized = normalize_deals(
            [deal(1, 100, 0, 0, 0.1, 2300), deal(2, 100, 1, 1, 0.1, 2310, profit=100)],
            CONSTANTS,
        )
        trade = reconstruct_closed_positions(normalized)[0]
        self.assertEqual(trade.side, "buy")
        self.assertEqual(trade.entry_price, 2300)
        self.assertEqual(trade.exit_price, 2310)
        self.assertEqual(trade.broker_deal_ids, ["1", "2"])

    def test_reconstructs_sell_position(self):
        normalized = normalize_deals(
            [deal(3, 101, 0, 1, 0.2, 2320), deal(4, 101, 1, 0, 0.2, 2300, profit=400)],
            CONSTANTS,
        )
        trade = reconstruct_closed_positions(normalized)[0]
        self.assertEqual(trade.side, "sell")
        self.assertEqual(trade.volume, 0.2)
        self.assertEqual(trade.gross_profit, 400)

    def test_partial_entries_and_partial_closes_form_one_trade(self):
        normalized = normalize_deals(
            [
                deal(5, 102, 0, 0, 0.1, 2300),
                deal(6, 102, 0, 0, 0.2, 2303),
                deal(7, 102, 1, 1, 0.1, 2310, profit=100),
                deal(8, 102, 1, 1, 0.2, 2315, profit=240),
            ],
            CONSTANTS,
        )
        trades = reconstruct_closed_positions(normalized)
        self.assertEqual(len(trades), 1)
        self.assertAlmostEqual(trades[0].volume, 0.3)
        self.assertAlmostEqual(trades[0].entry_price, 2302)
        self.assertAlmostEqual(trades[0].exit_price, 2313.3333333333333)
        self.assertEqual(len(trades[0].broker_deal_ids), 4)

    def test_open_partial_position_is_not_imported(self):
        normalized = normalize_deals(
            [deal(9, 103, 0, 0, 0.2, 2300), deal(10, 103, 1, 1, 0.1, 2310)],
            CONSTANTS,
        )
        self.assertEqual(reconstruct_closed_positions(normalized), [])

    def test_duplicate_deal_ticket_is_deduplicated(self):
        raw = deal(11, 104, 0, 0, 0.1, 2300)
        normalized = normalize_deals([raw, raw, deal(12, 104, 1, 1, 0.1, 2310)], CONSTANTS)
        self.assertEqual([item.broker_deal_id for item in normalized], ["11", "12"])


if __name__ == "__main__":
    unittest.main()
