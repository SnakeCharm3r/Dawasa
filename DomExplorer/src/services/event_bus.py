from __future__ import annotations

from collections import defaultdict
from typing import Callable, DefaultDict, List


class EventBus:
    """Simple internal event dispatcher for collectors and services."""

    def __init__(self) -> None:
        self._subscribers: DefaultDict[str, List[Callable[[dict], None]]] = defaultdict(list)

    def subscribe(self, event_name: str, handler: Callable[[dict], None]) -> None:
        self._subscribers[event_name].append(handler)

    def publish(self, event_name: str, payload: dict | None = None) -> None:
        for handler in self._subscribers.get(event_name, []):
            handler(payload or {})
