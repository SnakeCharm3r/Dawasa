import type { NextRequest } from 'next/server';
import { getServerAuthClient, getSupabaseAdminClient } from './supabase-admin';

export class UnauthorizedError extends Error {}

export function readBearerToken(request: NextRequest) {
  const authorization = request.headers.get('authorization');
  if (!authorization?.startsWith('Bearer ')) throw new UnauthorizedError('Missing bearer token.');
  const token = authorization.slice('Bearer '.length).trim();
  if (!token) throw new UnauthorizedError('Missing bearer token.');
  return token;
}

export async function requireUser(request: NextRequest) {
  const token = readBearerToken(request);
  const { data, error } = await getServerAuthClient().auth.getUser(token);
  if (error || !data.user) throw new UnauthorizedError('Invalid or expired access token.');

  const { data: profile, error: profileError } = await getSupabaseAdminClient()
    .from('profiles')
    .select('is_active')
    .eq('id', data.user.id)
    .maybeSingle();
  if (profileError || !profile?.is_active) throw new UnauthorizedError('This account is inactive.');

  return { user: data.user, token };
}
