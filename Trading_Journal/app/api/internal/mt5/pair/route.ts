import { randomBytes } from 'crypto';
import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { hashSecret } from '@/lib/server/internal-auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';

const requestSchema = z.object({
  pairing_code: z.string().min(32).max(128),
}).strict();

export async function POST(request: NextRequest) {
  try {
    const { pairing_code: pairingCode } = requestSchema.parse(await request.json());
    const connectorToken = randomBytes(32).toString('base64url');
    const now = new Date().toISOString();

    // Matching and consuming the digest in one UPDATE makes the code single-use.
    const { data: account, error } = await getSupabaseAdminClient()
      .from('trading_accounts')
      .update({
        connector_token_hash: hashSecret(connectorToken),
        pairing_code_hash: null,
        pairing_expires_at: null,
        paired_at: now,
        updated_at: now,
      })
      .eq('pairing_code_hash', hashSecret(pairingCode))
      .gt('pairing_expires_at', now)
      .select('id,broker,platform,account_number,server')
      .maybeSingle();
    if (error) throw error;
    if (!account) {
      return NextResponse.json({ error: 'Pairing code is invalid, expired, or already used.' }, { status: 401 });
    }

    console.info(JSON.stringify({ event: 'mt5_connector_paired', account_id: account.id }));
    return NextResponse.json({
      trading_account_id: account.id,
      connector_token: connectorToken,
      account,
    });
  } catch (error) {
    return apiError(error);
  }
}
