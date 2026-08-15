-- Read-only broker synchronization foundations for Exness MetaTrader 5.
-- MT5 credentials intentionally do not belong in this schema.

alter table public.settings
  add column if not exists user_id uuid references auth.users(id) on delete cascade;
alter table public.settings alter column user_id set default auth.uid();

alter table public.strategies
  add column if not exists user_id uuid references auth.users(id) on delete cascade;
alter table public.strategies alter column user_id set default auth.uid();
alter table public.strategies drop constraint if exists strategies_name_key;

alter table public.trades
  add column if not exists user_id uuid references auth.users(id) on delete cascade,
  add column if not exists trading_account_id uuid,
  add column if not exists broker_deal_id text,
  add column if not exists broker_order_id text,
  add column if not exists broker_position_id text,
  add column if not exists broker_deal_ids text[] not null default '{}',
  add column if not exists side text,
  add column if not exists volume numeric,
  add column if not exists open_time timestamptz,
  add column if not exists close_time timestamptz,
  add column if not exists take_profit numeric,
  add column if not exists commission numeric not null default 0,
  add column if not exists swap numeric not null default 0,
  add column if not exists fee numeric not null default 0,
  add column if not exists gross_profit numeric,
  add column if not exists net_profit numeric,
  add column if not exists magic_number bigint,
  add column if not exists broker_comment text,
  add column if not exists source text not null default 'manual',
  add column if not exists raw_broker_metadata jsonb not null default '{}'::jsonb;
alter table public.trades alter column user_id set default auth.uid();

create table if not exists public.trading_accounts (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null references auth.users(id) on delete cascade,
  broker text not null default 'Exness',
  platform text not null default 'MT5',
  account_number text not null,
  server text not null,
  account_name text,
  account_currency text,
  account_type text,
  balance numeric,
  equity numeric,
  leverage integer,
  last_sync_at timestamptz,
  last_deal_time timestamptz,
  last_deal_ticket text,
  sync_requested_at timestamptz,
  history_start_at timestamptz,
  sync_status text not null default 'disconnected',
  sync_error text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint trading_accounts_broker_check check (broker = 'Exness'),
  constraint trading_accounts_platform_check check (platform = 'MT5'),
  constraint trading_accounts_sync_status_check check (
    sync_status in ('connected', 'syncing', 'failed', 'disconnected', 'terminal_offline')
  ),
  constraint trading_accounts_identity_key unique (
    user_id, broker, platform, account_number, server
  ),
  constraint trading_accounts_id_user_key unique (id, user_id)
);

do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conname = 'trades_trading_account_user_fkey'
      and conrelid = 'public.trades'::regclass
  ) then
    alter table public.trades
      add constraint trades_trading_account_user_fkey
      foreign key (trading_account_id, user_id)
      references public.trading_accounts(id, user_id);
  end if;
end $$;

do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conname = 'trades_source_check'
      and conrelid = 'public.trades'::regclass
  ) then
    alter table public.trades
      add constraint trades_source_check check (source in ('manual', 'mt5'));
  end if;
end $$;

do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conname = 'trades_side_check'
      and conrelid = 'public.trades'::regclass
  ) then
    alter table public.trades
      add constraint trades_side_check check (side is null or side in ('buy', 'sell'));
  end if;
end $$;

create table if not exists public.broker_deals (
  id uuid primary key default gen_random_uuid(),
  trading_account_id uuid not null,
  user_id uuid not null,
  broker_deal_id text not null,
  broker_order_id text,
  broker_position_id text not null,
  entry_type text not null,
  deal_type text not null,
  symbol text not null,
  side text,
  deal_time timestamptz not null,
  price numeric not null,
  volume numeric not null,
  profit numeric not null default 0,
  commission numeric not null default 0,
  swap numeric not null default 0,
  fee numeric not null default 0,
  magic_number bigint,
  comment text,
  raw_metadata jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint broker_deals_account_user_fkey
    foreign key (trading_account_id, user_id)
    references public.trading_accounts(id, user_id)
    on delete cascade,
  constraint broker_deals_account_ticket_key unique (trading_account_id, broker_deal_id),
  constraint broker_deals_side_check check (side is null or side in ('buy', 'sell'))
);

create table if not exists public.account_snapshots (
  id uuid primary key default gen_random_uuid(),
  trading_account_id uuid not null,
  user_id uuid not null,
  captured_at timestamptz not null default now(),
  balance numeric,
  equity numeric,
  margin numeric,
  free_margin numeric,
  currency text,
  leverage integer,
  raw_metadata jsonb not null default '{}'::jsonb,
  constraint account_snapshots_account_user_fkey
    foreign key (trading_account_id, user_id)
    references public.trading_accounts(id, user_id)
    on delete cascade
);

create index if not exists settings_user_id_idx on public.settings(user_id);
create unique index if not exists settings_one_per_user_idx
  on public.settings(user_id) where user_id is not null;
create index if not exists strategies_user_id_idx on public.strategies(user_id);
create unique index if not exists strategies_user_name_key
  on public.strategies(user_id, lower(name)) where user_id is not null;
create index if not exists trades_user_date_idx on public.trades(user_id, date desc);
create index if not exists trades_trading_account_id_idx on public.trades(trading_account_id);
create unique index if not exists trades_mt5_position_key
  on public.trades(trading_account_id, broker_position_id)
  where source = 'mt5' and trading_account_id is not null and broker_position_id is not null;
create index if not exists trading_accounts_user_status_idx
  on public.trading_accounts(user_id, sync_status);
create index if not exists broker_deals_user_time_idx
  on public.broker_deals(user_id, deal_time desc);
create index if not exists broker_deals_position_idx
  on public.broker_deals(trading_account_id, broker_position_id);
create index if not exists account_snapshots_account_time_idx
  on public.account_snapshots(trading_account_id, captured_at desc);

alter table public.trading_accounts enable row level security;
alter table public.broker_deals enable row level security;
alter table public.account_snapshots enable row level security;

-- Replace the original single-user anonymous policies with owner-scoped policies.
drop policy if exists "anon_select_strategies" on public.strategies;
drop policy if exists "anon_insert_strategies" on public.strategies;
drop policy if exists "anon_update_strategies" on public.strategies;
drop policy if exists "anon_delete_strategies" on public.strategies;
drop policy if exists "anon_select_settings" on public.settings;
drop policy if exists "anon_insert_settings" on public.settings;
drop policy if exists "anon_update_settings" on public.settings;
drop policy if exists "anon_delete_settings" on public.settings;
drop policy if exists "anon_select_trades" on public.trades;
drop policy if exists "anon_insert_trades" on public.trades;
drop policy if exists "anon_update_trades" on public.trades;
drop policy if exists "anon_delete_trades" on public.trades;

drop policy if exists "users_manage_own_strategies" on public.strategies;
create policy "users_manage_own_strategies" on public.strategies
  for all to authenticated
  using ((select auth.uid()) = user_id)
  with check ((select auth.uid()) = user_id);

drop policy if exists "users_manage_own_settings" on public.settings;
create policy "users_manage_own_settings" on public.settings
  for all to authenticated
  using ((select auth.uid()) = user_id)
  with check ((select auth.uid()) = user_id);

drop policy if exists "users_manage_own_trades" on public.trades;
create policy "users_manage_own_trades" on public.trades
  for all to authenticated
  using ((select auth.uid()) = user_id)
  with check ((select auth.uid()) = user_id);

drop policy if exists "users_manage_own_trading_accounts" on public.trading_accounts;
create policy "users_manage_own_trading_accounts" on public.trading_accounts
  for all to authenticated
  using ((select auth.uid()) = user_id)
  with check ((select auth.uid()) = user_id);

drop policy if exists "users_read_own_broker_deals" on public.broker_deals;
create policy "users_read_own_broker_deals" on public.broker_deals
  for select to authenticated
  using ((select auth.uid()) = user_id);

drop policy if exists "users_read_own_account_snapshots" on public.account_snapshots;
create policy "users_read_own_account_snapshots" on public.account_snapshots
  for select to authenticated
  using ((select auth.uid()) = user_id);

revoke all on public.settings, public.strategies, public.trades,
  public.trading_accounts, public.broker_deals, public.account_snapshots from anon;
grant select, insert, update, delete on public.settings, public.strategies,
  public.trades, public.trading_accounts to authenticated;
grant select on public.broker_deals, public.account_snapshots to authenticated;
grant select, insert, update, delete on public.settings, public.strategies,
  public.trades, public.trading_accounts, public.broker_deals,
  public.account_snapshots to service_role;

-- One short transaction handles deal idempotency, logical position upserts,
-- account snapshots, and sync cursor updates. Journal-only trade fields are
-- deliberately absent from the conflict update list.
create or replace function public.ingest_mt5_sync(
  p_trading_account_id uuid,
  p_account jsonb,
  p_deals jsonb,
  p_trades jsonb,
  p_last_deal_time timestamptz,
  p_last_deal_ticket text,
  p_sync_status text default 'connected',
  p_sync_error text default null
)
returns jsonb
language plpgsql
security invoker
set search_path = ''
as $$
declare
  v_user_id uuid;
  v_deal_count integer := 0;
  v_trade_count integer := 0;
begin
  select user_id into v_user_id
  from public.trading_accounts
  where id = p_trading_account_id
  for update;

  if v_user_id is null then
    raise exception 'Unknown trading account';
  end if;

  insert into public.broker_deals (
    trading_account_id, user_id, broker_deal_id, broker_order_id,
    broker_position_id, entry_type, deal_type, symbol, side, deal_time,
    price, volume, profit, commission, swap, fee, magic_number, comment,
    raw_metadata
  )
  select
    p_trading_account_id,
    v_user_id,
    d->>'broker_deal_id',
    d->>'broker_order_id',
    d->>'broker_position_id',
    d->>'entry_type',
    d->>'deal_type',
    d->>'symbol',
    nullif(d->>'side', ''),
    (d->>'deal_time')::timestamptz,
    (d->>'price')::numeric,
    (d->>'volume')::numeric,
    coalesce((d->>'profit')::numeric, 0),
    coalesce((d->>'commission')::numeric, 0),
    coalesce((d->>'swap')::numeric, 0),
    coalesce((d->>'fee')::numeric, 0),
    nullif(d->>'magic_number', '')::bigint,
    d->>'comment',
    coalesce(d->'raw_metadata', '{}'::jsonb)
  from jsonb_array_elements(coalesce(p_deals, '[]'::jsonb)) as d
  on conflict (trading_account_id, broker_deal_id) do update set
    broker_order_id = excluded.broker_order_id,
    broker_position_id = excluded.broker_position_id,
    entry_type = excluded.entry_type,
    deal_type = excluded.deal_type,
    symbol = excluded.symbol,
    side = excluded.side,
    deal_time = excluded.deal_time,
    price = excluded.price,
    volume = excluded.volume,
    profit = excluded.profit,
    commission = excluded.commission,
    swap = excluded.swap,
    fee = excluded.fee,
    magic_number = excluded.magic_number,
    comment = excluded.comment,
    raw_metadata = excluded.raw_metadata,
    updated_at = now();
  get diagnostics v_deal_count = row_count;

  insert into public.trades (
    user_id, trading_account_id, trade_number, date, symbol, direction,
    strategy, calc_mode, entry_price, exit_price, lot_size, fees,
    stop_loss, target_price, source, broker_deal_id, broker_order_id,
    broker_position_id, broker_deal_ids, side, volume, open_time,
    close_time, take_profit, commission, swap, fee, gross_profit,
    net_profit, magic_number, broker_comment, raw_broker_metadata
  )
  select
    v_user_id,
    p_trading_account_id,
    coalesce((t->>'trade_number')::integer,
      (select coalesce(max(trade_number), 0) from public.trades where user_id = v_user_id)
      + (row_number() over ())::integer),
    ((t->>'close_time')::timestamptz)::date,
    t->>'symbol',
    case when t->>'side' = 'buy' then 'Long' else 'Short' end,
    'Unreviewed',
    'pips',
    (t->>'entry_price')::numeric,
    (t->>'exit_price')::numeric,
    (t->>'volume')::numeric,
    abs(coalesce((t->>'commission')::numeric, 0))
      + abs(coalesce((t->>'fee')::numeric, 0)),
    nullif(t->>'stop_loss', '')::numeric,
    nullif(t->>'take_profit', '')::numeric,
    'mt5',
    t->>'broker_deal_id',
    t->>'broker_order_id',
    t->>'broker_position_id',
    coalesce(array(select jsonb_array_elements_text(t->'broker_deal_ids')), '{}'),
    t->>'side',
    (t->>'volume')::numeric,
    (t->>'open_time')::timestamptz,
    (t->>'close_time')::timestamptz,
    nullif(t->>'take_profit', '')::numeric,
    coalesce((t->>'commission')::numeric, 0),
    coalesce((t->>'swap')::numeric, 0),
    coalesce((t->>'fee')::numeric, 0),
    coalesce((t->>'gross_profit')::numeric, 0),
    coalesce((t->>'net_profit')::numeric, 0),
    nullif(t->>'magic_number', '')::bigint,
    t->>'comment',
    coalesce(t->'raw_metadata', '{}'::jsonb)
  from jsonb_array_elements(coalesce(p_trades, '[]'::jsonb)) as t
  on conflict (trading_account_id, broker_position_id)
    where source = 'mt5' and trading_account_id is not null and broker_position_id is not null
  do update set
    broker_deal_id = excluded.broker_deal_id,
    broker_order_id = excluded.broker_order_id,
    broker_deal_ids = excluded.broker_deal_ids,
    symbol = excluded.symbol,
    direction = excluded.direction,
    side = excluded.side,
    volume = excluded.volume,
    entry_price = excluded.entry_price,
    exit_price = excluded.exit_price,
    lot_size = excluded.lot_size,
    open_time = excluded.open_time,
    close_time = excluded.close_time,
    date = excluded.date,
    stop_loss = excluded.stop_loss,
    target_price = excluded.target_price,
    take_profit = excluded.take_profit,
    fees = excluded.fees,
    commission = excluded.commission,
    swap = excluded.swap,
    fee = excluded.fee,
    gross_profit = excluded.gross_profit,
    net_profit = excluded.net_profit,
    magic_number = excluded.magic_number,
    broker_comment = excluded.broker_comment,
    raw_broker_metadata = excluded.raw_broker_metadata,
    updated_at = now();
  get diagnostics v_trade_count = row_count;

  if p_account is not null and p_sync_status = 'connected' then
    insert into public.account_snapshots (
      trading_account_id, user_id, balance, equity, margin, free_margin,
      currency, leverage, raw_metadata
    ) values (
      p_trading_account_id,
      v_user_id,
      nullif(p_account->>'balance', '')::numeric,
      nullif(p_account->>'equity', '')::numeric,
      nullif(p_account->>'margin', '')::numeric,
      nullif(p_account->>'free_margin', '')::numeric,
      p_account->>'currency',
      nullif(p_account->>'leverage', '')::integer,
      coalesce(p_account->'raw_metadata', '{}'::jsonb)
    );
  end if;

  update public.trading_accounts set
    account_name = coalesce(p_account->>'name', account_name),
    account_currency = coalesce(p_account->>'currency', account_currency),
    balance = coalesce(nullif(p_account->>'balance', '')::numeric, balance),
    equity = coalesce(nullif(p_account->>'equity', '')::numeric, equity),
    leverage = coalesce(nullif(p_account->>'leverage', '')::integer, leverage),
    last_sync_at = case when p_sync_status = 'connected' then now() else last_sync_at end,
    last_deal_time = coalesce(p_last_deal_time, last_deal_time),
    last_deal_ticket = coalesce(p_last_deal_ticket, last_deal_ticket),
    sync_status = p_sync_status,
    sync_error = p_sync_error,
    updated_at = now()
  where id = p_trading_account_id;

  return jsonb_build_object(
    'deals_upserted', v_deal_count,
    'trades_upserted', v_trade_count,
    'sync_status', p_sync_status
  );
end;
$$;

revoke all on function public.ingest_mt5_sync(
  uuid, jsonb, jsonb, jsonb, timestamptz, text, text, text
) from public, anon, authenticated;
grant execute on function public.ingest_mt5_sync(
  uuid, jsonb, jsonb, jsonb, timestamptz, text, text, text
) to service_role;

-- Existing rows predate users and remain unowned. Assign them explicitly after
-- the first user signs up, for example in the SQL editor:
-- update public.trades set user_id = '<auth-user-uuid>' where user_id is null;
-- update public.settings set user_id = '<auth-user-uuid>' where user_id is null;
-- update public.strategies set user_id = '<auth-user-uuid>' where user_id is null;
