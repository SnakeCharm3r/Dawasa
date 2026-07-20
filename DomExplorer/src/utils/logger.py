from __future__ import annotations

import logging
import sys
from typing import Optional

from src.config import config


class AppLogger:
    """Console and database-backed application logger."""

    def __init__(self, name: str = "DomExplorer", db: Optional[object] = None) -> None:
        self.name = name
        self.db = db
        self.logger = logging.getLogger(name)
        self.logger.setLevel(getattr(logging, config.LOG_LEVEL.upper(), logging.INFO))
        if not self.logger.handlers:
            handler = logging.StreamHandler(sys.stdout)
            formatter = logging.Formatter("%(asctime)s [%(levelname)s] %(message)s")
            handler.setFormatter(formatter)
            self.logger.addHandler(handler)
        self.logger.propagate = False

    def info(self, message: str) -> None:
        self.logger.info(message)
        self._persist("INFO", message)

    def warning(self, message: str) -> None:
        self.logger.warning(message)
        self._persist("WARNING", message)

    def error(self, message: str) -> None:
        self.logger.error(message)
        self._persist("ERROR", message)

    def debug(self, message: str) -> None:
        self.logger.debug(message)
        self._persist("DEBUG", message)

    def _persist(self, level: str, message: str) -> None:
        if self.db is not None:
            try:
                self.db.log(level, message)
            except Exception:
                pass
