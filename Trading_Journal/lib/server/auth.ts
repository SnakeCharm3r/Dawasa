import type { NextRequest } from 'next/server';
import { getServerAuthClient } from './supabase-admin';

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
  return { user: data.user, token };
}
