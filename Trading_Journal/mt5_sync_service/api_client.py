from __future__ import annotations

import json
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from .config import Config


class ApiError(RuntimeError):
    pass


class JournalApiClient:
    def __init__(self, config: Config) -> None:
        self.config = config

    def get_account(self) -> dict[str, Any]:
        response = self._request({
            "action": "mt5_get_account",
            "account_id": self.config.trading_account_id,
        })
        account = response.get("account")
        if not isinstance(account, dict):
            raise ApiError("Malformed account response from journal API")
        return account

    def send_sync(self, payload: dict[str, Any]) -> dict[str, Any]:
        response = self._request({"action": "mt5_sync", "payload": payload})
        if "result" not in response:
            raise ApiError("Malformed sync response from journal API")
        return response

    @staticmethod
    def exchange_pairing(
        journal_api_url: str,
        publishable_key: str,
        pairing_code: str,
        timeout_seconds: int = 30,
    ) -> dict[str, Any]:
        body = json.dumps({"action": "mt5_pair", "pairing_code": pairing_code}).encode()
        request = Request(
            journal_api_url.rstrip("/"),
            data=body,
            method="POST",
            headers={"Content-Type": "application/json", "apikey": publishable_key},
        )
        return JournalApiClient._open(request, timeout_seconds)

    def _request(self, payload: dict[str, Any]) -> dict[str, Any]:
        body = json.dumps(payload).encode()
        request = Request(
            self.config.journal_api_url,
            data=body,
            method="POST",
            headers={
                "Content-Type": "application/json",
                "apikey": self.config.journal_public_key,
                "Authorization": f"Bearer {self.config.journal_internal_token}",
            },
        )
        return self._open(request, self.config.request_timeout_seconds)

    @staticmethod
    def _open(request: Request, timeout_seconds: int) -> dict[str, Any]:
        try:
            with urlopen(request, timeout=timeout_seconds) as response:
                decoded = json.loads(response.read().decode())
                if not isinstance(decoded, dict):
                    raise ApiError("Journal API returned a non-object JSON response")
                return decoded
        except HTTPError as exc:
            message = exc.read().decode(errors="replace")[:1000]
            raise ApiError(f"Journal API returned HTTP {exc.code}: {message}") from exc
        except (URLError, TimeoutError, json.JSONDecodeError) as exc:
            raise ApiError(f"Journal API request failed: {exc}") from exc
