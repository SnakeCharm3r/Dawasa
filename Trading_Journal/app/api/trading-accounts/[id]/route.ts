import { NextRequest, NextResponse } from 'next/server';
import { requireUser } from '@/lib/server/auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';
import { getOwnedTradingAccount, publicTradingAccount } from '@/lib/server/trading-accounts';

export const dynamic = 'force-dynamic';

export async function GET(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    const { user } = await requireUser(request);
    const account = await getOwnedTradingAccount(params.id, user.id);
    if (!account) return NextResponse.json({ error: 'Trading account not found.' }, { status: 404 });
    return NextResponse.json({ account: publicTradingAccount(account) });
  } catch (error) {
    return apiError(error);
  }
}

export async function DELETE(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    const { user } = await requireUser(request);
    const account = await getOwnedTradingAccount(params.id, user.id);
    if (!account) return NextResponse.json({ error: 'Trading account not found.' }, { status: 404 });
    const { error } = await getSupabaseAdminClient()
      .from('trading_accounts')
      .update({
        sync_status: 'disconnected',
        sync_requested_at: null,
        connector_token_hash: null,
        pairing_code_hash: null,
        pairing_expires_at: null,
        paired_at: null,
        updated_at: new Date().toISOString(),
      })
      .eq('id', account.id)
      .eq('user_id', user.id);
    if (error) throw error;
    console.info(JSON.stringify({ event: 'mt5_account_disconnected', user_id: user.id, account_id: account.id }));
    return NextResponse.json({ status: 'disconnected' });
  } catch (error) {
    return apiError(error);
  }
}
