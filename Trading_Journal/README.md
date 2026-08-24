# Trading Journal

A Next.js trading journal with multi-broker MetaTrader 5 account preparation and a read-only synchronization adapter.

## Architecture

```text
MT5 broker account
  -> local/VPS MetaTrader 5 terminal
  -> Python read-only sync service
  -> authenticated internal Next.js REST API
  -> Supabase Auth, journal data, and login-activity audit data
```

The sync service contains no order placement or position-modification methods.
Broker passwords submitted through the Accounts page are encrypted with AES-256-GCM
on the Next.js server and are never returned by the API. Use an investor/read-only
password whenever the broker supports one.

## Next.js setup

1. Copy `.env.example` to `.env.local`.
2. Set the public Supabase URL and publishable key. These identify the project;
   never put a Supabase secret/service-role key in a browser form.
3. Set a server-only Supabase secret key and a long random internal sync token.
4. Apply all files in `supabase/migrations` to Supabase.
5. Start the journal:

```bash
npm install
npm run dev
```

In Supabase **Authentication → Providers**, keep email enabled and enable Google.
For Google, configure the Google OAuth client ID/secret and add the application
origin and Supabase callback URL in Google Cloud. Add the application URL to the
Supabase redirect allow list. The journal supports Google, email/password, and
name/email/password account creation. The initial `/` URL always opens `/login`;
authenticated users continue to `/journal`. Journal navigation and data are not
rendered until a session exists.

Admin access is role-based and is never tied to a hard-coded email address. To
bootstrap the first administrator, open the user in Supabase Authentication and
set `app_metadata.role` to `admin`, then have that user sign out and back in to
refresh the session. Administrators receive an additional **Admin** navigation
item while retaining the complete journal. From there, they can review user
details, activate/deactivate accounts, and assign or remove administrator roles.
Passwords are never read or displayed.

Administrators also receive a **Login Activity** page backed by Supabase. It records
registration, login, logout, and failed-login events with server-derived IP and
device information. Location is explicitly approximate. Configure
`IP_GEOLOCATION_URL` and its optional token for public-IP enrichment. Set
`TRUST_PROXY_HEADERS=true` only when a trusted Nginx or Cloudflare proxy replaces
incoming forwarding headers; development accepts localhost proxy headers.

Journal settings, strategies, trades, entry/exit reasons, and emotional reflections
are written directly to Supabase under the signed-in user's RLS-protected account.
The post-import browser IndexedDB backup remains enabled.

Email confirmation is temporarily bypassed by the server registration route.
New accounts are created as confirmed, receive a success toast, and return to the
Sign in view. Set `NEXT_PUBLIC_REQUIRE_EMAIL_CONFIRMATION=true` to restore the
existing confirmation-email branch.

Sign in, open **Accounts**, choose a broker, and add the MT5 account ID, password,
and server. Open **Settings** to choose the inactivity period after which the
current browser session is automatically signed out.

If upgrading an existing single-user database, assign pre-existing unowned rows
to the first authenticated user's UUID using the commented statements at the end
of the MT5 migration.

## Python MT5 service setup

Run the service on the Windows machine or VPS where the official MetaTrader 5
terminal is installed and logged into the Exness account.

```powershell
py -m venv .venv
.venv\Scripts\activate
pip install -r mt5_sync_service\requirements.txt
copy mt5_sync_service\.env.example mt5_sync_service\.env
```

The connector can still request the MT5 password privately on its host machine:

1. In **Accounts**, select **Pair connector**.
2. Copy the generated command to the Windows/VPS machine running MT5.
3. Run it with `--watch` for continuous synchronization.
4. Enter the MT5 login, password, server, and terminal path at the local prompts.

The pairing code is single-use and expires after ten minutes. The journal stores
only SHA-256 digests of the pairing code and account-scoped connector token. The
Python service never logs the password. For unattended restarts, load
`MT5_PASSWORD` and the remaining values from `mt5_sync_service/.env` or an
operating-system secret manager; never commit that file.

Perform the first read-only synchronization:

```powershell
python -m mt5_sync_service.main --pair PASTE_ONE_TIME_CODE_HERE
```

Run continuously (default interval: five minutes):

```powershell
python -m mt5_sync_service.main --pair PASTE_ONE_TIME_CODE_HERE --watch
```

Choose initial history with `MT5_INITIAL_HISTORY_DAYS=30`, `90`, `180`, or `365`.
Later runs use the database cursor with a configurable overlap window, while
unique deal and position constraints keep imports idempotent.

## Validation

```bash
npm run lint
npm run typecheck
npm test
npm run build
npm run test:python
```

The Python tests do not require a live MT5 terminal. A real synchronization does.

## Production hosting and OAuth

Set these variables in the hosting provider before building:

```text
NEXT_PUBLIC_SUPABASE_URL=https://YOUR_PROJECT_REF.supabase.co
NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY=sb_publishable_...
NEXT_PUBLIC_SITE_URL=https://YOUR_APP_DOMAIN
NEXT_PUBLIC_REQUIRE_EMAIL_CONFIRMATION=false
SUPABASE_SECRET_KEY=sb_secret_...
INTERNAL_SYNC_API_TOKEN=...
BROKER_CREDENTIAL_ENCRYPTION_KEY=...
TRUST_PROXY_HEADERS=true
IP_GEOLOCATION_URL=https://approved-provider.example/v1/{ip}
IP_GEOLOCATION_API_TOKEN=...
```

Only the `NEXT_PUBLIC_` values may be included in browser code. The Supabase secret,
internal sync token, and broker encryption key must remain server-only. Generate
the encryption key as a long random value; if omitted, the app derives an isolated
encryption key from `SUPABASE_SECRET_KEY`.

In Supabase **Authentication → URL Configuration**, set the Site URL to the HTTPS
production origin and add both the production URL and `http://localhost:3000/**`
to the redirect allow list. In Google Cloud, add the production origin under
Authorized JavaScript origins. Keep the Google redirect URI set to the Supabase
callback shown in the Google provider configuration.

This repository is configured for a Node-capable Netlify deployment through
`netlify.toml`. InfinityFree free hosting cannot run Node.js or Next.js server
features, so it cannot serve this complete application: the authenticated
trading-account and MT5 API routes require the Next.js server. An InfinityFree
domain may redirect to the Node deployment, but do not upload `.env.local`, the
Supabase secret key, or the raw Next.js source to InfinityFree.

The supplied InfinityFree domain details and production URL checklist are in
`deployment/infinityfree`. The browser-safe build variables for
`https://milanolodge.gt.tc` are in `.env.infinityfree.example`.
