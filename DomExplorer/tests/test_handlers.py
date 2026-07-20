import unittest

from src.api.handlers import MessageHandler


class MessageHandlerTests(unittest.TestCase):
    def test_handler_stores_connected_event(self):
        handler = MessageHandler()

        response = handler.on_connected("client")

        self.assertEqual(response["event"], "connected")
        self.assertEqual(response["client"], "client")

    def test_handler_stores_message_payload(self):
        handler = MessageHandler()

        class FakeMessage:
            payloadType = None
            payload = b""

        response = handler.on_message("client", FakeMessage())

        self.assertEqual(response["event"], "message")
        self.assertEqual(response["message"].payloadType, None)
        self.assertEqual(response["decoded_type"], None)
