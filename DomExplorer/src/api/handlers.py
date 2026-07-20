from typing import Any, Optional

try:
    from google.protobuf.json_format import MessageToDict
except ImportError:
    MessageToDict = None

try:
    from ctrader_open_api import Protobuf
except ImportError:
    Protobuf = None


class MessageHandler:

    def __init__(self):

        self.authentication = None
        self.symbols = None
        self.tick_store = None
        self.statistics_collector = None
        self.market_depth = None
        self.execution = None
        self.symbol_store = None
        self.client = None
        self.focus_symbol = "XAUUSD"

        self.application_authenticated = False
        self.account_authenticated = False

        self.last_event = None


    def set_authentication(self, authentication):

        self.authentication = authentication


    def set_symbols_collector(self, symbols):

        self.symbols = symbols

    def set_tick_store(self, tick_store):

        self.tick_store = tick_store

    def set_statistics_collector(self, statistics_collector):

        self.statistics_collector = statistics_collector

    def set_market_depth_collector(self, market_depth_collector):

        self.market_depth = market_depth_collector

    def set_execution_collector(self, execution_collector):

        self.execution = execution_collector

    def set_symbol_store(self, symbol_store):

        self.symbol_store = symbol_store

    def set_focus_symbol(self, focus_symbol):

        self.focus_symbol = focus_symbol


    def on_Connected(self, client):

        return self.on_connected(client)


    def on_connected(self, client):

        self.client = client

        event = {
            "event": "connected",
            "client": client,
        }

        self.last_event = event


        print("\n==============================")
        print("✓ CALLBACK RECEIVED: CONNECTED")
        print("==============================")


        if self.authentication:

            print("Authenticating application...")

            self.authentication.authenticate_application()


        return event



    def on_disconnected(self, client, reason):

        event = {
            "event": "disconnected",
            "client": client,
            "reason": reason,
        }


        self.last_event = event


        print("\n==============================")
        print("✗ CALLBACK RECEIVED: DISCONNECTED")
        print("==============================")

        print(reason)

        return event



    def on_message(self, client, message):


        print("\n==============================")
        print("✓ CALLBACK RECEIVED: MESSAGE")
        print("==============================")


        # ------------------------------------------------------------
        # Decode ProtoMessage into actual Open API message
        # ------------------------------------------------------------

        try:
            if Protobuf is None:
                raise RuntimeError("ctrader_open_api is unavailable")

            decoded = Protobuf.extract(message)

            decoded_type = type(decoded).__name__

            print(
                "Decoded Message:",
                decoded_type
            )


        except Exception as e:

            print(
                "Decode failed:",
                e
            )

            decoded = None

            decoded_type = None



        event = {

            "event": "message",

            "client": client,

            "message": message,

            "decoded": decoded,

            "decoded_type": decoded_type,

        }


        self.last_event = event



        # ------------------------------------------------------------
        # Debug output
        # ------------------------------------------------------------

        try:
            if MessageToDict is None:
                raise RuntimeError("google.protobuf.json_format is unavailable")

            data = MessageToDict(
                decoded,
                preserving_proto_field_name=True
            )

            print(data)


        except Exception:

            print(decoded)



        # ============================================================
        # Authentication Response
        # ============================================================


        if decoded_type == "ProtoOAApplicationAuthRes":


            self.application_authenticated = True


            print(
                "\n✓ Application authentication successful"
            )


            if self.authentication:

                print(
                    "Authenticating trading account..."
                )

                self.authentication.authenticate_account()



        elif decoded_type == "ProtoOAAccountAuthRes":


            self.account_authenticated = True


            print(
                "\n✓ Trading account authentication successful"
            )


            if self.symbols:

                print(
                    "Requesting symbols..."
                )

                self.symbols.request_symbols()



        # ============================================================
        # Symbols Response
        # ============================================================


        elif decoded_type == "ProtoOASymbolsListRes":


            print(
                "\n✓ Symbols received"
            )


            if self.symbols:

                self.symbols.handle_response(
                    decoded
                )

            self._subscribe_to_focus_symbol()


        # ============================================================
        # Depth of Market (DOM) Flow
        # ============================================================

        elif decoded_type == "ProtoOASubscribeDepthQuotesRes":
            print("\n✓ Depth quotes subscription confirmed")

        elif decoded_type == "ProtoOAUnsubscribeDepthQuotesRes":
            print("\n✓ Depth quotes unsubscription confirmed")

        elif decoded_type == "ProtoOADepthEvent":
            self._handle_depth_event(decoded)

        elif decoded_type == "ProtoOAExecutionEvent":
            self._handle_execution_event(decoded)

        # ============================================================
        # Spot / Tick Price Flow
        # ============================================================

        elif decoded_type == "ProtoOASubscribeSpotsRes":
            print("\n✓ Spot subscription confirmed")

        elif decoded_type == "ProtoOAUnsubscribeSpotsRes":
            print("\n✓ Spot unsubscription confirmed")

        elif decoded_type == "ProtoOASpotEvent":
            self._handle_spot_event(decoded)

        elif decoded_type == "ProtoOAErrorRes":
            print(
                "\n⚠ API Error:",
                getattr(decoded, "errorCode", "unknown"),
                getattr(decoded, "description", ""),
            )

        # ============================================================
        # Tick / Market Data Flow
        # ============================================================

        elif decoded_type in {"ProtoOATickReq", "ProtoOATick"}:
            self._handle_tick(decoded)

        else:


            print(
                "\nUnhandled message:",
                decoded_type
            )



        print("==============================\n")


        return event

    def _handle_tick(self, decoded):
        if self.tick_store is None:
            return

        payload = {
            "symbol": getattr(decoded, "symbolName", None) or getattr(decoded, "symbol", None),
            "bid": getattr(decoded, "bid", None),
            "ask": getattr(decoded, "ask", None),
            "spread": getattr(decoded, "spread", None),
            "time": getattr(decoded, "timestamp", None),
        }

        if payload.get("bid") is None or payload.get("ask") is None:
            return

        self.tick_store.update(
            payload["symbol"],
            float(payload["bid"]),
            float(payload["ask"]),
            payload["time"],
        )

        if self.statistics_collector is not None:
            self.statistics_collector.update(payload)

    def _resolve_symbol(self, symbol_id: Any) -> Optional[str]:
        if symbol_id is None or self.symbol_store is None:
            return None
        sym = self.symbol_store.get_by_id(symbol_id)
        if sym:
            return sym.get("symbol_name")
        return None

    def _handle_spot_event(self, decoded):
        if self.tick_store is None:
            return

        symbol = self._resolve_symbol(getattr(decoded, "symbolId", None))
        if symbol is None:
            return

        bid = getattr(decoded, "bid", None)
        ask = getattr(decoded, "ask", None)
        if bid is None or ask is None:
            return

        self.tick_store.update(
            symbol,
            float(bid),
            float(ask),
            getattr(decoded, "timestamp", None),
        )

        if self.statistics_collector is not None:
            self.statistics_collector.update({
                "symbol": symbol,
                "bid": float(bid),
                "ask": float(ask),
                "spread": float(ask) - float(bid),
                "time": getattr(decoded, "timestamp", None),
            })

    def _handle_depth_event(self, decoded):
        symbol = self._resolve_symbol(getattr(decoded, "symbolId", None))
        if symbol is None or self.market_depth is None:
            return

        new_quotes = []
        for quote in (getattr(decoded, "newQuotes", []) or []):
            new_quotes.append({
                "id": getattr(quote, "id", None),
                "size": getattr(quote, "size", 0),
                "bid": getattr(quote, "bid", None),
                "ask": getattr(quote, "ask", None),
            })

        deleted_ids = list(getattr(decoded, "deletedQuotes", []) or [])

        self.market_depth.apply_event(symbol, new_quotes, deleted_ids)

        top = self.market_depth.top_of_book(symbol)
        if top and self.tick_store is not None:
            bid = top.get("bid")
            ask = top.get("ask")
            if bid is not None and ask is not None:
                self.tick_store.update(
                    symbol,
                    float(bid),
                    float(ask),
                    getattr(decoded, "timestamp", None),
                )

    def _handle_execution_event(self, decoded):
        if self.execution is None:
            return
        self.execution.handle_execution_event(decoded)

    def _subscribe_to_focus_symbol(self):
        if self.client is None or self.authentication is None or self.symbol_store is None:
            return

        if Protobuf is None:
            print("\n⚠ cTrader protobuf SDK unavailable; skipping market data subscription")
            return

        from src.config import config

        subscribe = [self.focus_symbol]
        extra = [s for s in getattr(config, "SUBSCRIBE_SYMBOLS", []) if s and s != self.focus_symbol]
        subscribe.extend(extra)

        account_id = self.authentication.account_id

        for symbol_name in subscribe:
            focus = self.symbol_store.get(symbol_name)
            if focus is None:
                print(f"\n⚠ Symbol {symbol_name} not found in symbol list; skipping subscription")
                continue

            symbol_id = focus.get("symbol_id")

            try:
                req = Protobuf.get("ProtoOASubscribeDepthQuotesReq")
                req.ctidTraderAccountId = account_id
                req.symbolId.append(symbol_id)
                self.client.send(req)
                print(f"\n→ Subscribed to DOM depth quotes for {symbol_name}")
            except Exception as exc:
                print(f"\n⚠ Depth subscription failed for {symbol_name}: {exc}")

            try:
                req = Protobuf.get("ProtoOASubscribeSpotsReq")
                req.ctidTraderAccountId = account_id
                req.symbolId.append(symbol_id)
                req.subscribeToSpotTimestamp = True
                self.client.send(req)
                print(f"→ Subscribed to spot prices for {symbol_name}")
            except Exception as exc:
                print(f"\n⚠ Spot subscription failed for {symbol_name}: {exc}")
