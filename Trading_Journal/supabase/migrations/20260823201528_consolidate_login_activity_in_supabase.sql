-- Store registration summaries and authentication activity in Supabase.
-- Exact IP/device data is deliberately available only to trusted server code.

alter table public.profiles
  add column if not exists registered_ip inet,
  add column if not exists registered_country text,
  add column if not exists registered_city text,
  add column if not exists registered_at timestamptz;

alter table public.profiles
  drop constraint if exists profiles_registered_country_length_check,
  drop constraint if exists profiles_registered_city_length_check;

alter table public.profiles
  add constraint profiles_registered_country_length_check
    check (registered_country is null or char_length(registered_country) <= 100),
  add constraint profiles_registered_city_length_check
    check (registered_city is null or char_length(registered_city) <= 120);

create table if not exists public.login_activity_logs (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references auth.users(id) on delete set null,
  attempted_email text,
  event_type text not null,
  ip_address inet,
  country text,
  city text,
  timezone text,
  location_source text,
  user_agent text,
  browser text,
  operating_system text,
  device_type text,
  success boolean not null,
  failure_reason text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint login_activity_event_type_check
    check (event_type in ('registered', 'login', 'logout', 'failed_login')),
  constraint login_activity_attempted_email_length_check
    check (attempted_email is null or char_length(attempted_email) <= 320),
  constraint login_activity_country_length_check
    check (country is null or char_length(country) <= 100),
  constraint login_activity_city_length_check
    check (city is null or char_length(city) <= 120),
  constraint login_activity_timezone_length_check
    check (timezone is null or char_length(timezone) <= 100),
  constraint login_activity_location_source_length_check
    check (location_source is null or char_length(location_source) <= 80),
  constraint login_activity_browser_length_check
    check (browser is null or char_length(browser) <= 100),
  constraint login_activity_operating_system_length_check
    check (operating_system is null or char_length(operating_system) <= 100),
  constraint login_activity_device_type_length_check
    check (device_type is null or char_length(device_type) <= 50),
  constraint login_activity_failure_reason_length_check
    check (failure_reason is null or char_length(failure_reason) <= 255)
);

create index if not exists login_activity_logs_user_created_idx
  on public.login_activity_logs (user_id, created_at desc);
create index if not exists login_activity_logs_ip_address_idx
  on public.login_activity_logs (ip_address);
create index if not exists login_activity_logs_event_created_idx
  on public.login_activity_logs (event_type, created_at desc);
create index if not exists login_activity_logs_created_idx
  on public.login_activity_logs (created_at desc);

alter table public.login_activity_logs enable row level security;

revoke all on public.login_activity_logs from public, anon, authenticated;
grant select, insert, update, delete on public.login_activity_logs to service_role;

-- The view gives trusted API code profile fields alongside each activity event.
-- security_invoker keeps the underlying table's RLS behavior intact.
create or replace view public.login_activity_admin
with (security_invoker = true)
as
select
  log.id,
  log.user_id,
  log.attempted_email,
  log.event_type,
  host(log.ip_address) as ip_address,
  log.country,
  log.city,
  log.timezone,
  log.location_source,
  log.user_agent,
  log.browser,
  log.operating_system,
  log.device_type,
  log.success,
  log.failure_reason,
  log.created_at,
  log.updated_at,
  profile.email,
  coalesce(profile.display_name, profile.username) as display_name
from public.login_activity_logs as log
left join public.profiles as profile on profile.id = log.user_id;

revoke all on public.login_activity_admin from public, anon, authenticated;
grant select on public.login_activity_admin to service_role;

revoke update (
  registered_ip, registered_country, registered_city, registered_at
) on public.profiles from authenticated;
