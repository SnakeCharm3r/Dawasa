-- Admin-facing account metadata and active-account enforcement.
-- Passwords remain exclusively in Supabase Auth and are never copied here.

alter table public.profiles
  add column if not exists country text,
  add column if not exists is_active boolean not null default true;

alter table public.profiles
  drop constraint if exists profiles_country_length_check;

alter table public.profiles
  add constraint profiles_country_length_check
  check (country is null or char_length(trim(country)) between 2 and 100);

create index if not exists profiles_active_created_idx
  on public.profiles (is_active, created_at desc);

-- Keep safe signup metadata synchronized. Role/authorization data deliberately
-- stays in raw_app_meta_data and is never sourced from user-editable metadata.
create or replace function private.sync_user_profile()
returns trigger
language plpgsql
security definer
set search_path = ''
as $$
declare
  v_username text;
  v_display_name text;
  v_avatar_url text;
  v_country text;
begin
  v_username := nullif(trim(coalesce(
    new.raw_user_meta_data->>'user_name',
    new.raw_user_meta_data->>'preferred_username',
    new.raw_user_meta_data->>'username',
    ''
  )), '');
  v_display_name := nullif(trim(coalesce(
    new.raw_user_meta_data->>'full_name',
    new.raw_user_meta_data->>'name',
    ''
  )), '');
  v_avatar_url := nullif(trim(coalesce(
    new.raw_user_meta_data->>'avatar_url',
    new.raw_user_meta_data->>'picture',
    ''
  )), '');
  v_country := nullif(trim(coalesce(new.raw_user_meta_data->>'country', '')), '');

  insert into public.profiles (
    id, email, username, display_name, avatar_url, country, created_at, updated_at
  ) values (
    new.id, new.email, v_username, v_display_name, v_avatar_url, v_country,
    coalesce(new.created_at, now()), now()
  )
  on conflict (id) do update set
    email = excluded.email,
    username = coalesce(public.profiles.username, excluded.username),
    display_name = coalesce(excluded.display_name, public.profiles.display_name),
    avatar_url = coalesce(excluded.avatar_url, public.profiles.avatar_url),
    country = coalesce(excluded.country, public.profiles.country),
    updated_at = now();

  return new;
end;
$$;

revoke all on function private.sync_user_profile() from public, anon, authenticated;

create or replace function private.is_active_user(p_user_id uuid)
returns boolean
language sql
stable
security definer
set search_path = ''
as $$
  select coalesce((
    select profile.is_active
    from public.profiles as profile
    where profile.id = p_user_id
  ), false);
$$;

revoke all on function private.is_active_user(uuid) from public, anon, authenticated;

-- Restrictive policies compose with the existing ownership policies using AND.
-- This immediately prevents a deactivated account from using an existing JWT
-- against any journal table exposed through the Data API.
drop policy if exists "active_users_only" on public.profiles;
create policy "active_users_only" on public.profiles
  as restrictive for all to authenticated
  using ((select private.is_active_user((select auth.uid()))))
  with check ((select private.is_active_user((select auth.uid()))));

drop policy if exists "active_users_only" on public.strategies;
create policy "active_users_only" on public.strategies
  as restrictive for all to authenticated
  using ((select private.is_active_user((select auth.uid()))))
  with check ((select private.is_active_user((select auth.uid()))));

drop policy if exists "active_users_only" on public.settings;
create policy "active_users_only" on public.settings
  as restrictive for all to authenticated
  using ((select private.is_active_user((select auth.uid()))))
  with check ((select private.is_active_user((select auth.uid()))));

drop policy if exists "active_users_only" on public.trades;
create policy "active_users_only" on public.trades
  as restrictive for all to authenticated
  using ((select private.is_active_user((select auth.uid()))))
  with check ((select private.is_active_user((select auth.uid()))));

drop policy if exists "active_users_only" on public.trading_accounts;
create policy "active_users_only" on public.trading_accounts
  as restrictive for all to authenticated
  using ((select private.is_active_user((select auth.uid()))))
  with check ((select private.is_active_user((select auth.uid()))));

drop policy if exists "active_users_only" on public.broker_deals;
create policy "active_users_only" on public.broker_deals
  as restrictive for select to authenticated
  using ((select private.is_active_user((select auth.uid()))));

drop policy if exists "active_users_only" on public.account_snapshots;
create policy "active_users_only" on public.account_snapshots
  as restrictive for select to authenticated
  using ((select private.is_active_user((select auth.uid()))));

revoke update (is_active, country) on public.profiles from authenticated;
grant select, insert, update, delete on public.profiles to service_role;
