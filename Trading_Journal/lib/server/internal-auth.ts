import { createHash, timingSafeEqual } from 'crypto';
import type { NextRequest } from 'next/server';
import { UnauthorizedError } from './auth';
import { getSupabaseAdminClient } from './supabase-admin';

export function tokensMatch(actual: string | null, expected: string | undefined) {
  if (!actual || !expected) return false;
  const actualBuffer = Buffer.from(actual);
  const expectedBuffer = Buffer.from(expected);
  return actualBuffer.length === expectedBuffer.length && timingSafeEqual(actualBuffer, expectedBuffer);
}

export function requireInternalSyncToken(request: NextRequest) {
  const supplied = request.headers.get('x-internal-api-token');
  if (!tokensMatch(supplied, process.env.INTERNAL_SYNC_API_TOKEN)) {
    throw new UnauthorizedError('Invalid internal sync token.');
  }
}

export function hashSecret(value: string) {
  return createHash('sha256').update(value, 'utf8').digest('hex');
}

function readConnectorToken(request: NextRequest) {
  const authorization = request.headers.get('authorization');
  if (!authorization?.startsWith('Bearer ')) return null;
  return authorization.slice('Bearer '.length).trim() || null;
}

export async function requireConnectorToken(request: NextRequest, accountId: string) {
  const legacyToken = request.headers.get('x-internal-api-token');
  if (tokensMatch(legacyToken, process.env.INTERNAL_SYNC_API_TOKEN)) return;

  const supplied = readConnectorToken(request);
  if (!supplied) throw new UnauthorizedError('Missing connector token.');

  const { data, error } = await getSupabaseAdminClient()
    .from('trading_accounts')
    .select('connector_token_hash')
    .eq('id', accountId)
    .maybeSingle();
  if (error) throw error;
  if (!tokensMatch(hashSecret(supplied), data?.connector_token_hash)) {
    throw new UnauthorizedError('Invalid connector token.');
  }
}
