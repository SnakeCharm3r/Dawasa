import { createClient, type SupabaseClient } from '@supabase/supabase-js';

let adminClient: SupabaseClient | undefined;
let authClient: SupabaseClient | undefined;

function getSupabaseUrl() {
  const url = process.env.NEXT_PUBLIC_SUPABASE_URL;
  if (!url) throw new Error('NEXT_PUBLIC_SUPABASE_URL is not configured.');
  return url;
}

function getPublishableKey() {
  const key =
    process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY ??
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY;
  if (!key) throw new Error('A Supabase publishable key is not configured.');
  return key;
}

export function getServerAuthClient() {
  if (!authClient) {
    authClient = createClient(getSupabaseUrl(), getPublishableKey(), {
      auth: { autoRefreshToken: false, persistSession: false, detectSessionInUrl: false },
    });
  }
  return authClient;
}

export function getSupabaseAdminClient() {
  if (!adminClient) {
    const secret = process.env.SUPABASE_SECRET_KEY ?? process.env.SUPABASE_SERVICE_ROLE_KEY;
    if (!secret) throw new Error('SUPABASE_SECRET_KEY is not configured on the Next.js server.');
    adminClient = createClient(getSupabaseUrl(), secret, {
      auth: { autoRefreshToken: false, persistSession: false, detectSessionInUrl: false },
    });
  }
  return adminClient;
}
