from __future__ import annotations

from collections import defaultdict, deque
from typing import Deque, Dict, Optional


class TickStore:
    """Maintains the latest quote for each symbol and rolling history."""

    def __init__(self, history_size: int = 1000) -> None:
        self.history_size = history_size
        self._latest: Dict[str, dict] = {}
        self._history: Dict[str, Deque[dict]] = defaultdict(lambda: deque(maxlen=self.history_size))

    def update(self, symbol: str, bid: float, ask: float, timestamp: Optional[str] = None) -> dict:
        payload = {"symbol": symbol, "bid": bid, "ask": ask, "spread": ask - bid, "time": timestamp}
        self._latest[symbol] = payload
        self._history[symbol].append(payload)
        return payload

    def get(self, symbol: str) -> Optional[dict]:
        return self._latest.get(symbol)

    def remove(self, symbol: str) -> None:
        self._latest.pop(symbol, None)
        self._history.pop(symbol, None)

    def clear(self) -> None:
        self._latest.clear()
        self._history.clear()

    def count(self) -> int:
        return len(self._latest)

    def snapshot(self) -> Dict[str, dict]:
        return dict(self._latest)

    def history(self, symbol: str) -> Deque[dict]:
        return self._history.get(symbol, deque(maxlen=self.history_size))
