import websocket


class CTraderSocket:

    HOST = "wss://demo.ctraderapi.com:5035"


    def __init__(self):

        self.ws = None


    def connect(self):

        print("Connecting to cTrader...")

        self.ws = websocket.create_connection(
            self.HOST
        )

        print("Connected")


    def close(self):

        if self.ws:

            self.ws.close()

            print("Connection closed")