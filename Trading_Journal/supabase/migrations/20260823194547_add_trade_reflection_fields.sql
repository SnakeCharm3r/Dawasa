alter table public.trades
  add column if not exists exit_reason text,
  add column if not exists emotion_during_trade text;

comment on column public.trades.setup_notes is
  'Trader reflection explaining the setup and why the trade was entered.';
comment on column public.trades.exit_reason is
  'Trader reflection explaining why the trade was exited.';
comment on column public.trades.emotion_during_trade is
  'Trader reflection describing emotional state while the trade was open.';
