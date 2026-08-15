import { NextRequest, NextResponse } from 'next/server';
import { requireUser } from '@/lib/server/auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';
import { getOwnedTradingAccount } from '@/lib/server/trading-accounts';

export async function POST(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    const { user } = await requireUser(request);
    const account = await getOwnedTradingAccount(params.id, user.id);
    if (!account) return NextResponse.json({ error: 'Trading account not found.' }, { status: 404 });
    const requestedAt = new Date().toISOString();
    const { error } = await getSupabaseAdminClient()
      .from('trading_accounts')
      .update({ sync_status: 'syncing', sync_requested_at: requestedAt, sync_error: null, updated_at: requestedAt })
      .eq('id', account.id)
      .eq('user_id', user.id);
    if (error) throw error;
    console.info(JSON.stringify({ event: 'mt5_sync_requested', user_id: user.id, account_id: account.id }));
    return NextResponse.json({ status: 'syncing', requested_at: requestedAt }, { status: 202 });
  } catch (error) {
    return apiError(error);
  }
}
