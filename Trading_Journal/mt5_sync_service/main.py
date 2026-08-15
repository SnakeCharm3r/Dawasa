from __future__ import annotations

import argparse
import getpass
import logging
import os
import time

from .account_service import AccountService
from .api_client import JournalApiClient
from .config import Config
from .logging_config import configure_logging, log_event
from .mt5_client import Mt5Client, Mt5Error
from .trade_sync_service import TradeSyncService


logger = logging.getLogger(__name__)


def _prompt(name: str, default: str | None = None, *, secret: bool = False) -> str:
    existing = os.getenv(name, "").strip()
    if existing:
        return existing
    suffix = f" [{default}]" if default else ""
    value = getpass.getpass(f"{name}{suffix}: ") if secret else input(f"{name}{suffix}: ")
    value = value.strip() or (default or "")
    if not value:
        raise ValueError(f"{name} is required")
    return value


def config_from_pairing(pairing_code: str) -> Config:
    journal_url = _prompt(
        "JOURNAL_API_URL",
        "https://nbtnbdmezdwgdkgdxoip.supabase.co/functions/v1/journal-api",
    ).rstrip("/")
    publishable_key = _prompt("JOURNAL_SUPABASE_PUBLISHABLE_KEY")
    timeout = max(5, int(os.getenv("JOURNAL_API_TIMEOUT_SECONDS", "30")))
    pairing = JournalApiClient.exchange_pairing(
        journal_url, publishable_key, pairing_code, timeout
    )
    account = pairing.get("account")
    token = pairing.get("connector_token")
    account_id = pairing.get("trading_account_id")
    if not isinstance(account, dict) or not isinstance(token, str) or not isinstance(account_id, str):
        raise ValueError("Journal API returned a malformed pairing response")

    history_days = int(os.getenv("MT5_INITIAL_HISTORY_DAYS", "90"))
    if history_days not in {30, 90, 180, 365}:
        raise ValueError("MT5_INITIAL_HISTORY_DAYS must be 30, 90, 180, or 365")

    return Config(
        mt5_login=int(_prompt("MT5_LOGIN", str(account.get("account_number", "")))),
        mt5_password=_prompt("MT5_PASSWORD", secret=True),
        mt5_server=_prompt("MT5_SERVER", str(account.get("server", ""))),
        mt5_terminal_path=_prompt(
            "MT5_TERMINAL_PATH", r"C:\Program Files\MetaTrader 5\terminal64.exe"
        ),
        journal_api_url=journal_url,
        journal_public_key=publishable_key,
        journal_internal_token=token,
        trading_account_id=account_id,
        initial_history_days=history_days,
        overlap_minutes=max(1, int(os.getenv("MT5_SYNC_OVERLAP_MINUTES", "15"))),
        sync_interval_seconds=max(30, int(os.getenv("MT5_SYNC_INTERVAL_SECONDS", "300"))),
        request_timeout_seconds=timeout,
    )


def run_once(config: Config) -> None:
    client = Mt5Client(config)
    api = JournalApiClient(config)
    service = TradeSyncService(config, client, AccountService(client, config), api)
    connected = False
    try:
        log_event(logger, logging.INFO, "mt5_connection_attempt", account_id=config.trading_account_id)
        client.connect()
        connected = True
        service.sync_once()
    except Mt5Error as exc:
        status = "failed" if connected else "terminal_offline"
        log_event(logger, logging.ERROR, "mt5_terminal_failure", account_id=config.trading_account_id, status=status, error=str(exc))
        try:
            service.report_failure(status, str(exc))
        except Exception:
            logger.exception("failed_to_report_mt5_terminal_failure")
        raise
    finally:
        client.shutdown()


def main() -> None:
    parser = argparse.ArgumentParser(description="Read-only Exness MT5 journal synchronization")
    parser.add_argument("--watch", action="store_true", help="Synchronize continuously")
    parser.add_argument(
        "--pair",
        metavar="CODE",
        help="Exchange a one-time journal pairing code and prompt locally for MT5 credentials",
    )
    args = parser.parse_args()
    configure_logging()
    config = config_from_pairing(args.pair) if args.pair else Config.from_env()
    while True:
        try:
            run_once(config)
        except Exception as exc:
            log_event(logger, logging.ERROR, "mt5_sync_failed", error=str(exc))
            if not args.watch:
                raise
        if not args.watch:
            break
        time.sleep(config.sync_interval_seconds)


if __name__ == "__main__":
    main()
