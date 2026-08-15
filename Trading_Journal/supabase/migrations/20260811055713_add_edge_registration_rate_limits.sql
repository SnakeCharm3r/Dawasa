-- Database-backed rate limiting for the public Edge registration action.

create table if not exists public.registration_rate_limits (
  client_hash text primary key,
  window_started_at timestamptz not null,
  attempts integer not null default 1,
  updated_at timestamptz not null default now(),
  constraint registration_rate_limits_attempts_check check (attempts between 1 and 1000)
);

alter table public.registration_rate_limits enable row level security;
revoke all on public.registration_rate_limits from public, anon, authenticated;
grant select, insert, update, delete on public.registration_rate_limits to service_role;

create or replace function public.take_registration_attempt(
  p_client_hash text,
  p_now timestamptz default now(),
  p_window_seconds integer default 900,
  p_max_attempts integer default 5
)
returns jsonb
language plpgsql
security definer
set search_path = ''
as $$
declare
  v_row public.registration_rate_limits%rowtype;
  v_window interval;
begin
  if p_client_hash is null or char_length(p_client_hash) <> 64 then
    raise exception 'Invalid client hash';
  end if;
  if p_window_seconds < 60 or p_max_attempts < 1 then
    raise exception 'Invalid rate-limit configuration';
  end if;

  v_window := make_interval(secs => p_window_seconds);

  insert into public.registration_rate_limits (
    client_hash, window_started_at, attempts, updated_at
  ) values (
    p_client_hash, p_now, 1, p_now
  )
  on conflict (client_hash) do update set
    window_started_at = case
      when public.registration_rate_limits.window_started_at + v_window <= p_now
        then p_now
      else public.registration_rate_limits.window_started_at
    end,
    attempts = case
      when public.registration_rate_limits.window_started_at + v_window <= p_now
        then 1
      else least(public.registration_rate_limits.attempts + 1, p_max_attempts + 1)
    end,
    updated_at = p_now
  returning * into v_row;

  return jsonb_build_object(
    'allowed', v_row.attempts <= p_max_attempts,
    'remaining', greatest(0, p_max_attempts - v_row.attempts),
    'retry_after_seconds', greatest(
      0,
      ceil(extract(epoch from ((v_row.window_started_at + v_window) - p_now)))::integer
    )
  );
end;
$$;

revoke all on function public.take_registration_attempt(text, timestamptz, integer, integer)
  from public, anon, authenticated;
grant execute on function public.take_registration_attempt(text, timestamptz, integer, integer)
  to service_role;
