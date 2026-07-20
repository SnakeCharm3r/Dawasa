try:
    from ctrader_open_api.messages.OpenApiMessages_pb2 import (
        ProtoOAApplicationAuthReq,
        ProtoOAAccountAuthReq
    )
except ImportError:
    ProtoOAApplicationAuthReq = None
    ProtoOAAccountAuthReq = None

from src.config import config


class Authentication:

    def __init__(self, client):

        self.client = client
        self.account_id = None

        account_id_value = getattr(config, "CTRADER_ACCOUNT_ID", None)

        if account_id_value is not None:
            try:
                self.account_id = int(account_id_value)
            except (TypeError, ValueError):
                self.account_id = account_id_value

    def authenticate_application(self):

        print("Authenticating application...")

        if ProtoOAApplicationAuthReq is None:
            print("cTrader protobuf classes are unavailable; skipping application authentication.")
            return

        request = ProtoOAApplicationAuthReq()

        request.clientId = config.CTRADER_CLIENT_ID
        request.clientSecret = config.CTRADER_CLIENT_SECRET

        self.client.send(request)

        print("Application authentication request sent")

    def authenticate_account(self):

        print("Authenticating account...")

        if ProtoOAAccountAuthReq is None:
            print("cTrader protobuf classes are unavailable; skipping account authentication.")
            return

        request = ProtoOAAccountAuthReq()

        request.accessToken = config.CTRADER_ACCESS_TOKEN
        request.ctidTraderAccountId = int(
            config.CTRADER_ACCOUNT_ID
        )

        self.client.send(request)

        print("Account authentication request sent")