import requests

from src.auth.token_manager import TokenManager


class CTraderClient:

    BASE_URL = "https://api.spotware.com"


    def __init__(self):

        self.token_manager = TokenManager()

        self.access_token = (
            self.token_manager
            .get_access_token()
        )


    def _headers(self):

        return {
            "Authorization": f"Bearer {self.access_token}",
            "Content-Type": "application/json"
        }


    def get(self, endpoint):

        url = (
            self.BASE_URL
            +
            endpoint
        )


        response = requests.get(
            url,
            headers=self._headers()
        )


        response.raise_for_status()

        return response.json()