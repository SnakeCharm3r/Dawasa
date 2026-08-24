-- RLS policies execute this private helper as the authenticated caller. The
-- helper is not exposed through the Data API, but authenticated must be able to
-- execute it for the policies to evaluate.
grant execute on function private.is_active_user(uuid) to authenticated;
