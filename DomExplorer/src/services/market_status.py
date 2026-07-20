from __future__ import annotations

import logging
from datetime import datetime
from typing import Any, List, Optional

from src.config import config


class MarketStatusService:
    """Tracks whether markets are open per symbol and emits a signal when closed.

    Forex/metal instruments follow a daily window (``MARKET_OPEN_HOUR`` -
    ``MARKET_CLOSE_HOUR``) and are fully closed over the weekend. Symbols listed
    in ``MARKET_ALWAYS_OPEN_SYMBOLS`` (e.g. BTCUSD) trade 24/7 and never report
    a weekend close. A ``MarketStatus`` event is published on the supplied
    ``EventBus`` whenever the overall market state changes.
    """

    def __init__(self, event_bus: Optional[Any] = None, logger: Optional[Any] = None) -> None:
        self.event_bus = event_bus
        self._logger = logger or logging.getLogger("MarketStatus")
        self._last_state: Optional[bool] = None

    def is_weekend(self, when: Optional[datetime] = None) -> bool:
        when = when or datetime.now()
        # Monday=0 ... Sunday=6
        return when.weekday() >= 5

    def is_within_session(self, when: Optional[datetime] = None) -> bool:
        when = when or datetime.now()
        return config.MARKET_OPEN_HOUR <= when.hour < config.MARKET_CLOSE_HOUR

    def is_symbol_open(self, symbol: str, when: Optional[datetime] = None) -> bool:
        symbol = (symbol or "").upper()
        if symbol in config.MARKET_ALWAYS_OPEN_SYMBOLS:
            return True
        when = when or datetime.now()
        if self.is_weekend(when):
            return False
        return self.is_within_session(when)

    def markets_closed(self, symbols: Optional[List[str]] = None, when: Optional[datetime] = None) -> bool:
        """True when the watched traditional market is fully closed.

        If ``symbols`` is empty, falls back to the configured subscribe list plus
        the focus symbol. Crypto-only watch lists are never "closed".
        """
        if not symbols:
            symbols = list(config.SUBSCRIBE_SYMBOLS) + [config.FOCUS_SYMBOL]
        when = when or datetime.now()
        traditional = [s for s in symbols if s not in config.MARKET_ALWAYS_OPEN_SYMBOLS]
        if not traditional:
            return False
        return all(not self.is_symbol_open(s, when) for s in traditional)

    def check(self, symbols: Optional[List[str]] = None, when: Optional[datetime] = None) -> dict:
        when = when or datetime.now()
        closed = self.markets_closed(symbols, when)
        state = {
            "closed": closed,
            "is_weekend": self.is_weekend(when),
            "session_open": self.is_within_session(when),
            "checked_at": when.isoformat(timespec="seconds"),
        }
        if self.event_bus is not None and closed != self._last_state:
            self.event_bus.publish("MarketStatus", state)
            if closed:
                self._logger.info("Markets closed signal emitted (weekend/session end).")
            else:
                self._logger.info("Markets open signal emitted.")
        self._last_state = closed
        return state
