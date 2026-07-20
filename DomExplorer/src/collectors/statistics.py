from __future__ import annotations

from typing import Any


class StatisticsCollector:
    """Calculates rolling statistics for incoming tick data."""

    def __init__(self, tick_store: Any) -> None:
        self.tick_store = tick_store
        self.tick_count = 0
        self.total_spread = 0.0
        self.total_mid = 0.0

    def update(self, payload: dict) -> dict:
        self.tick_count += 1
        spread = payload.get("spread", 0.0)
        bid = payload.get("bid", 0.0)
        ask = payload.get("ask", 0.0)
        mid = (bid + ask) / 2.0
        self.total_spread += spread
        self.total_mid += mid

        stats = {
            "tick_count": self.tick_count,
            "spread": spread,
            "mid_price": mid,
            "average_spread": self.total_spread / self.tick_count,
            "average_mid": self.total_mid / self.tick_count,
        }
        return stats
