import { randomBytes } from 'crypto';
import { NextRequest, NextResponse } from 'next/server';
import { requireUser } from '@/lib/server/auth';
import { hashSecret } from '@/lib/server/internal-auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';
import { getOwnedTradingAccount } from '@/lib/server/trading-accounts';

const PAIRING_WINDOW_MINUTES = 10;

export async function POST(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    const { user } = await requireUser(request);
    const account = await getOwnedTradingAccount(params.id, user.id);
    if (!account) return NextResponse.json({ error: 'Trading account not found.' }, { status: 404 });

    const pairingCode = randomBytes(32).toString('base64url');
    const expiresAt = new Date(Date.now() + PAIRING_WINDOW_MINUTES * 60_000).toISOString();
    const { error } = await getSupabaseAdminClient()
      .from('trading_accounts')
      .update({
        pairing_code_hash: hashSecret(pairingCode),
        pairing_expires_at: expiresAt,
        connector_token_hash: null,
        paired_at: null,
        sync_status: 'disconnected',
        sync_error: null,
        updated_at: new Date().toISOString(),
      })
      .eq('id', account.id)
      .eq('user_id', user.id);
    if (error) throw error;

    return NextResponse.json({ pairing_code: pairingCode, expires_at: expiresAt });
  } catch (error) {
    return apiError(error);
  }
}
