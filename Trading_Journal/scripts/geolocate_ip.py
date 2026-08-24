#!/usr/bin/env python3
"""Best-effort IP geolocation for server-side login activity enrichment."""

from __future__ import annotations

import ipaddress
import json
import os
import sys
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import Request, urlopen


def result(country=None, city=None, timezone=None, source="unavailable"):
    return {"country": country, "city": city, "timezone": timezone, "source": source}


def geolocate(value: str):
    try:
        address = ipaddress.ip_address(value)
    except ValueError:
        return result(source="invalid_ip")

    local_timezone = os.environ.get("TZ")
    if address.is_loopback:
        return result(country="Local Network", timezone=local_timezone, source="local")
    if address.is_private or address.is_link_local:
        return result(country="Internal Network", timezone=local_timezone, source="internal")

    template = os.environ.get("IP_GEOLOCATION_URL", "").strip()
    if not template:
        return result(source="not_configured")

    url = template.replace("{ip}", str(address))
    headers = {"Accept": "application/json", "User-Agent": "TradingJournal/1.0"}
    token = os.environ.get("IP_GEOLOCATION_API_TOKEN", "").strip()
    if token:
        header = os.environ.get("IP_GEOLOCATION_AUTH_HEADER", "Authorization")
        value = token if header.lower() != "authorization" else f"Bearer {token}"
        headers[header] = value

    try:
        with urlopen(Request(url, headers=headers), timeout=4) as response:
            payload = json.loads(response.read().decode("utf-8"))
        return result(
            country=payload.get("country_name") or payload.get("country"),
            city=payload.get("city"),
            timezone=(payload.get("timezone") or {}).get("id")
            if isinstance(payload.get("timezone"), dict)
            else payload.get("timezone"),
            source=urlparse(url).netloc or "configured_provider",
        )
    except (HTTPError, URLError, TimeoutError, json.JSONDecodeError, OSError):
        return result(source="provider_error")


def main():
    if len(sys.argv) != 2:
        print(json.dumps(result(source="missing_ip")))
        return 2
    print(json.dumps(geolocate(sys.argv[1]), separators=(",", ":")))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
