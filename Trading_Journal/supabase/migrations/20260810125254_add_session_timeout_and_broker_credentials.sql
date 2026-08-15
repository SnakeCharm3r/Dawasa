-- Per-user idle timeout preferences and encrypted MT5 credential storage.

alter table public.settings
  add column if not exists idle_timeout_minutes integer not null default 30;

do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conname = 'settings_idle_timeout_minutes_check'
      and conrelid = 'public.settings'::regclass
  ) then
    alter table public.settings
      add constraint settings_idle_timeout_minutes_check
      check (idle_timeout_minutes = 0 or idle_timeout_minutes between 5 and 1440);
  end if;
end $$;

alter table public.trading_accounts
  drop constraint if exists trading_accounts_broker_check;

alter table public.trading_accounts
  add column if not exists broker_password_encrypted text;

do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conname = 'trading_accounts_broker_length_check'
      and conrelid = 'public.trading_accounts'::regclass
  ) then
    alter table public.trading_accounts
      add constraint trading_accounts_broker_length_check
      check (char_length(trim(broker)) between 2 and 80);
  end if;
end $$;

-- Trading accounts are managed only through owner-authenticated server routes.
-- This prevents encrypted credentials and connector hashes from being selected
-- directly through the browser Data API.
revoke all on public.trading_accounts from authenticated;
grant select, insert, update, delete on public.trading_accounts to service_role;
