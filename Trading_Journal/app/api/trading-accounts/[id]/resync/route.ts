import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { requireUser } from '@/lib/server/auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';
import { getOwnedTradingAccount } from '@/lib/server/trading-accounts';

const schema = z.object({ days: z.union([z.literal(30), z.literal(90), z.literal(180), z.literal(365)]) });

export async function POST(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    const { user } = await requireUser(request);
    const { days } = schema.parse(await request.json());
    const account = await getOwnedTradingAccount(params.id, user.id);
    if (!account) return NextResponse.json({ error: 'Trading account not found.' }, { status: 404 });
    const now = new Date();
    const { error } = await getSupabaseAdminClient()
      .from('trading_accounts')
      .update({
        history_start_at: new Date(now.getTime() - days * 86400000).toISOString(),
        last_deal_time: null,
        last_deal_ticket: null,
        sync_status: 'syncing',
        sync_requested_at: now.toISOString(),
        sync_error: null,
        updated_at: now.toISOString(),
      })
      .eq('id', account.id)
      .eq('user_id', user.id);
    if (error) throw error;
    console.info(JSON.stringify({ event: 'mt5_history_resync_requested', user_id: user.id, account_id: account.id, days }));
    return NextResponse.json({ status: 'syncing', history_days: days }, { status: 202 });
  } catch (error) {
    return apiError(error);
  }
}
