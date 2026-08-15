-- Application-readable user profiles synchronized from Supabase Auth.
-- Authentication secrets remain exclusively in the managed auth schema.

create schema if not exists private;
revoke all on schema private from public, anon, authenticated;

create table if not exists public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  email text,
  username text,
  display_name text,
  avatar_url text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint profiles_username_length_check
    check (username is null or char_length(username) between 3 and 40),
  constraint profiles_display_name_length_check
    check (display_name is null or char_length(display_name) <= 120)
);

create unique index if not exists profiles_username_key
  on public.profiles (lower(username))
  where username is not null;

alter table public.profiles enable row level security;

drop policy if exists "users_read_own_profile" on public.profiles;
create policy "users_read_own_profile" on public.profiles
  for select to authenticated
  using ((select auth.uid()) = id);

drop policy if exists "users_update_own_profile" on public.profiles;
create policy "users_update_own_profile" on public.profiles
  for update to authenticated
  using ((select auth.uid()) = id)
  with check ((select auth.uid()) = id);

revoke all on public.profiles from anon, authenticated;
grant select on public.profiles to authenticated;
grant update (username, display_name, avatar_url) on public.profiles to authenticated;
grant select, insert, update, delete on public.profiles to service_role;

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

  insert into public.profiles (
    id, email, username, display_name, avatar_url, created_at, updated_at
  ) values (
    new.id, new.email, v_username, v_display_name, v_avatar_url,
    coalesce(new.created_at, now()), now()
  )
  on conflict (id) do update set
    email = excluded.email,
    username = coalesce(public.profiles.username, excluded.username),
    display_name = coalesce(excluded.display_name, public.profiles.display_name),
    avatar_url = coalesce(excluded.avatar_url, public.profiles.avatar_url),
    updated_at = now();

  return new;
end;
$$;

revoke all on function private.sync_user_profile() from public, anon, authenticated;

drop trigger if exists on_auth_user_profile_sync on auth.users;
create trigger on_auth_user_profile_sync
  after insert or update of email, raw_user_meta_data on auth.users
  for each row execute function private.sync_user_profile();

-- Backfill profiles if this migration is applied after users already exist.
insert into public.profiles (
  id, email, username, display_name, avatar_url, created_at, updated_at
)
select
  id,
  email,
  nullif(trim(coalesce(
    raw_user_meta_data->>'user_name',
    raw_user_meta_data->>'preferred_username',
    raw_user_meta_data->>'username',
    ''
  )), ''),
  nullif(trim(coalesce(
    raw_user_meta_data->>'full_name',
    raw_user_meta_data->>'name',
    ''
  )), ''),
  nullif(trim(coalesce(
    raw_user_meta_data->>'avatar_url',
    raw_user_meta_data->>'picture',
    ''
  )), ''),
  coalesce(created_at, now()),
  now()
from auth.users
on conflict (id) do nothing;
