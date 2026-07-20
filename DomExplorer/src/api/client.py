try:
    from ctrader_open_api import Client
    from ctrader_open_api import EndPoints
    from ctrader_open_api import TcpProtocol
except ImportError:
    Client = None
    EndPoints = None
    TcpProtocol = None

from src.api.handlers import MessageHandler


class CTraderClient:

    def __init__(self):

        if Client is None or EndPoints is None or TcpProtocol is None:
            self.client = None
            print("cTrader SDK unavailable; running in offline mode.")
        else:
            self.client = Client(
                EndPoints.PROTOBUF_DEMO_HOST,
                EndPoints.PROTOBUF_PORT,
                TcpProtocol
            )

        self.handler = MessageHandler()


    def connect(self):

        if self.client is None:
            print("Skipping cTrader connection because the SDK is unavailable.")
            return

        print("Connecting to cTrader Open API...")

        self.client.setConnectedCallback(
            self.handler.on_connected
        )

        self.client.setDisconnectedCallback(
            self.handler.on_disconnected
        )

        self.client.setMessageReceivedCallback(
            self.handler.on_message
        )

        self.client.startService()

        print("cTrader service started")


    def disconnect(self):

        if self.client is not None:
            self.client.stopService()

        print("Disconnected")


    def get_client(self):

        return self.client