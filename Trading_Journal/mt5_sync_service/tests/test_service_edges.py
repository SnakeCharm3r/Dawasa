from __future__ import annotations

import json
import unittest
from datetime import datetime, timedelta, timezone
from unittest.mock import patch

from mt5_sync_service.api_client import ApiError, JournalApiClient
from mt5_sync_service.config import Config
from mt5_sync_service.mt5_client import Mt5Client, Mt5Error
from mt5_sync_service.trade_sync_service import incremental_range


def config() -> Config:
    return Config(
        mt5_login=123,
        mt5_password="not-logged",
        mt5_server="Exness-Test",
        mt5_terminal_path="terminal64.exe",
        journal_api_url="https://journal.test/functions/v1/journal-api",
        journal_public_key="publishable-key",
        journal_internal_token="internal-token",
        trading_account_id="00000000-0000-4000-8000-000000000001",
    )


class OfflineMt5:
    def initialize(self, **_kwargs):
        return False

    def last_error(self):
        return (-10005, "IPC timeout")

    def shutdown(self):
        return None


class Response:
    def __enter__(self):
        return self

    def __exit__(self, *_args):
        return False

    def read(self):
        return json.dumps(["not", "an", "object"]).encode()


class JsonResponse(Response):
    def __init__(self, value):
        self.value = value

    def read(self):
        return json.dumps(self.value).encode()


class ServiceEdgeTests(unittest.TestCase):
    def test_incremental_sync_uses_overlap(self):
        now = datetime(2026, 8, 8, tzinfo=timezone.utc)
        start, end = incremental_range(
            {"last_deal_time": "2026-08-07T12:00:00+00:00"}, now, 90, 15
        )
        self.assertEqual(start, datetime(2026, 8, 7, 11, 45, tzinfo=timezone.utc))
        self.assertEqual(end, now)

    def test_initial_sync_uses_configured_history(self):
        now = datetime(2026, 8, 8, tzinfo=timezone.utc)
        start, _ = incremental_range({}, now, 90, 15)
        self.assertEqual(start, now - timedelta(days=90))

    def test_disconnected_terminal_reports_last_error(self):
        client = Mt5Client(config(), module=OfflineMt5())
        with self.assertRaisesRegex(Mt5Error, "IPC timeout"):
            client.connect()

    @patch("mt5_sync_service.api_client.urlopen", return_value=Response())
    def test_malformed_api_response_is_rejected(self, _urlopen):
        client = JournalApiClient(config())
        with self.assertRaisesRegex(ApiError, "non-object"):
            client.get_account()

    @patch(
        "mt5_sync_service.api_client.urlopen",
        return_value=JsonResponse(
            {
                "trading_account_id": "account-id",
                "connector_token": "connector-token",
                "account": {"account_number": "123", "server": "Exness-Test"},
            }
        ),
    )
    def test_pairing_exchange_sends_no_mt5_credentials(self, mocked_urlopen):
        response = JournalApiClient.exchange_pairing(
            "https://journal.test/functions/v1/journal-api",
            "publishable-key",
            "one-time-pairing-code-that-is-long-enough",
        )
        self.assertEqual(response["connector_token"], "connector-token")
        request = mocked_urlopen.call_args.args[0]
        self.assertEqual(
            json.loads(request.data.decode()),
            {
                "action": "mt5_pair",
                "pairing_code": "one-time-pairing-code-that-is-long-enough",
            },
        )
        self.assertEqual(request.headers["Apikey"], "publishable-key")
        self.assertNotIn("MT5_PASSWORD", request.data.decode())


if __name__ == "__main__":
    unittest.main()
