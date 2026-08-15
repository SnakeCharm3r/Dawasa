-- Short-lived connector pairing. Raw pairing codes and connector tokens are
-- returned once to the caller; only SHA-256 digests are stored.

alter table public.trading_accounts
  add column if not exists pairing_code_hash text,
  add column if not exists pairing_expires_at timestamptz,
  add column if not exists connector_token_hash text,
  add column if not exists paired_at timestamptz;

create unique index if not exists trading_accounts_pairing_code_hash_key
  on public.trading_accounts(pairing_code_hash)
  where pairing_code_hash is not null;

-- Trading-account mutations go through authenticated Next.js routes. This
-- prevents owners from changing connector authentication columns directly via
-- the Data API while preserving owner-scoped read access.
revoke insert, update, delete on public.trading_accounts from authenticated;
grant select on public.trading_accounts to authenticated;
