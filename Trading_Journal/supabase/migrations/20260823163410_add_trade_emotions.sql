alter table public.trades
  add column if not exists emotions text[] not null default '{}'::text[];

alter table public.trades
  drop constraint if exists trades_emotions_count_check;

alter table public.trades
  add constraint trades_emotions_count_check
  check (
    cardinality(emotions) <= 12
    and array_position(emotions, null) is null
  );

comment on column public.trades.emotions is
  'Trader-selected emotional states associated with this trade.';
