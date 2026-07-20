from urllib.parse import urlencode
from src.config import config


class CTraderOAuth:

    AUTH_URL = "https://id.ctrader.com/my/settings/openapi/grantingaccess/"


    def __init__(self):

        self.client_id = config.CTRADER_CLIENT_ID
        self.client_secret = config.CTRADER_CLIENT_SECRET
        self.redirect_uri = config.CTRADER_REDIRECT_URI


    def get_authorization_url(self):

        params = {
            "client_id": self.client_id,
            "redirect_uri": self.redirect_uri,
            "scope": "trading",
            "product": "web"
        }

        return (
            self.AUTH_URL
            + "?"
            + urlencode(params)
        )
        
        