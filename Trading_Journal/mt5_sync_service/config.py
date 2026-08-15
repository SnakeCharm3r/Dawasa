from __future__ import annotations

import os
from dataclasses import dataclass


def _required(name: str) -> str:
    value = os.getenv(name, "").strip()
    if not value:
        raise ValueError(f"{name} is required")
    return value


@dataclass(frozen=True)
class Config:
    mt5_login: int
    mt5_password: str
    mt5_server: str
    mt5_terminal_path: str
    journal_api_url: str
    journal_public_key: str
    journal_internal_token: str
    trading_account_id: str
    initial_history_days: int = 90
    overlap_minutes: int = 15
    sync_interval_seconds: int = 300
    request_timeout_seconds: int = 30

    @classmethod
    def from_env(cls) -> "Config":
        history_days = int(os.getenv("MT5_INITIAL_HISTORY_DAYS", "90"))
        if history_days not in {30, 90, 180, 365}:
            raise ValueError("MT5_INITIAL_HISTORY_DAYS must be 30, 90, 180, or 365")
        return cls(
            mt5_login=int(_required("MT5_LOGIN")),
            mt5_password=_required("MT5_PASSWORD"),
            mt5_server=_required("MT5_SERVER"),
            mt5_terminal_path=_required("MT5_TERMINAL_PATH"),
            journal_api_url=_required("JOURNAL_API_URL").rstrip("/"),
            journal_public_key=_required("JOURNAL_SUPABASE_PUBLISHABLE_KEY"),
            journal_internal_token=(
                os.getenv("JOURNAL_CONNECTOR_TOKEN", "").strip()
                or _required("JOURNAL_INTERNAL_SYNC_TOKEN")
            ),
            trading_account_id=_required("JOURNAL_TRADING_ACCOUNT_ID"),
            initial_history_days=history_days,
            overlap_minutes=max(1, int(os.getenv("MT5_SYNC_OVERLAP_MINUTES", "15"))),
            sync_interval_seconds=max(30, int(os.getenv("MT5_SYNC_INTERVAL_SECONDS", "300"))),
            request_timeout_seconds=max(5, int(os.getenv("JOURNAL_API_TIMEOUT_SECONDS", "30"))),
        )
