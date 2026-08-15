-- Cover composite ownership foreign keys used by account-scoped joins/deletes.

create index if not exists trades_account_user_idx
  on public.trades (trading_account_id, user_id);

create index if not exists broker_deals_account_user_idx
  on public.broker_deals (trading_account_id, user_id);

create index if not exists account_snapshots_account_user_idx
  on public.account_snapshots (trading_account_id, user_id);
