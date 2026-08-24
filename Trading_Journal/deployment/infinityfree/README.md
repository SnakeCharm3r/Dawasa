# InfinityFree domain handoff

## Supplied domain

- Domain: `milanolodge.gt.tc`
- HTTPS origin: `https://milanolodge.gt.tc`
- InfinityFree document root: `htdocs`
- InfinityFree account: `if0_42054498`
- InfinityFree IP: `185.27.134.133`

## Deployment architecture

The browser application is exported as static HTML, CSS, and JavaScript for
InfinityFree. Authentication and journal data use Supabase directly. Account
creation, encrypted broker credentials, broker-account management, pairing,
and MT5 synchronization are handled by the deployed `journal-api` Supabase
Edge Function.

Never upload `.env.local`, `SUPABASE_SECRET_KEY`, `INTERNAL_SYNC_API_TOKEN`, or
`BROKER_CREDENTIAL_ENCRYPTION_KEY` into `htdocs`.

## Generate the upload folder

Set the real browser-safe Supabase publishable key in `.env.local`, then run:

```bash
npm run build:infinityfree
```

Upload the **contents** of `deployment/infinityfree/htdocs/` into the remote
InfinityFree `htdocs` directory. You can instead extract
`deployment/infinityfree/milanolodge-htdocs.zip` there.

No InfinityFree MySQL database or PHP API is used. Apply the repository's
`supabase/migrations` to the Supabase project before publishing the static build.
All journal inputs are stored in Supabase under row-level security.

Each successful manual report import also writes the latest JSON snapshot into
the browser's IndexedDB and downloads a dated `trading-journal-backup-*.json`
file. Browsers cannot silently write arbitrary server-side files; the download
is the portable system-file copy.

## Production authentication URLs

Configure Supabase Authentication URL settings with:

- Site URL: `https://milanolodge.gt.tc`
- Redirect URL: `https://milanolodge.gt.tc/login`
- Optional path allow-list: `https://milanolodge.gt.tc/**`

Configure the Google OAuth client with:

- Authorized JavaScript origin: `https://milanolodge.gt.tc`
- Authorized redirect URI: the Supabase Google callback URL shown in the
  Supabase provider configuration, normally
  `https://nbtnbdmezdwgdkgdxoip.supabase.co/auth/v1/callback`

Use `.env.infinityfree.example` as the browser-safe domain configuration. Server
secrets belong only on the external Node host or in Supabase Edge Function
secrets.
