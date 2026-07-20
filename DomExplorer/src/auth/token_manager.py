import json
import os
from datetime import datetime, timedelta

from src.config import config


TOKEN_FILE = "tokens.json"


class TokenManager:

    def __init__(self):
        self.token_file = TOKEN_FILE


    def save_tokens(self, token_data):

        data = {
            "access_token": token_data["access_token"],
            "refresh_token": token_data["refresh_token"],
            "expires_at": (
                datetime.now()
                +
                timedelta(
                    seconds=token_data.get(
                        "expires_in",
                        3600
                    )
                )
            ).isoformat()
        }


        with open(
            self.token_file,
            "w"
        ) as file:
            json.dump(
                data,
                file,
                indent=4
            )


    def load_tokens(self):

        if not os.path.exists(
            self.token_file
        ):
            return None


        with open(
            self.token_file,
            "r"
        ) as file:
            return json.load(file)


    def get_access_token(self):

        tokens = self.load_tokens()

        if tokens:
            return tokens["access_token"]

        return config.ACCESS_TOKEN