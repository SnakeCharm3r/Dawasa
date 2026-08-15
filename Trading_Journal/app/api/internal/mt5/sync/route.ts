import { NextRequest, NextResponse } from 'next/server';
import { mt5SyncPayloadSchema } from '@/lib/mt5/contracts';
import { requireConnectorToken } from '@/lib/server/internal-auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';

export async function POST(request: NextRequest) {
  const startedAt = Date.now();
  try {
    const payload = mt5SyncPayloadSchema.parse(await request.json());
    await requireConnectorToken(request, payload.trading_account_id);
    const admin = getSupabaseAdminClient();
    const { data: account, error: accountError } = await admin
      .from('trading_accounts')
      .select('id,account_number')
      .eq('id', payload.trading_account_id)
      .maybeSingle();
    if (accountError) throw accountError;
    if (!account) return NextResponse.json({ error: 'Trading account not found.' }, { status: 404 });
    if (payload.account.login && payload.account.login !== account.account_number) {
      return NextResponse.json({ error: 'Connected MT5 login does not match the registered account.' }, { status: 409 });
    }

    const { data, error } = await admin.rpc('ingest_mt5_sync', {
      p_trading_account_id: payload.trading_account_id,
      p_account: payload.account,
      p_deals: payload.deals,
      p_trades: payload.trades,
      p_last_deal_time: payload.last_deal_time,
      p_last_deal_ticket: payload.last_deal_ticket,
      p_sync_status: payload.sync_status,
      p_sync_error: payload.sync_error,
    });
    if (error) throw error;
    console.info(JSON.stringify({
      event: 'mt5_sync_ingested',
      account_id: payload.trading_account_id,
      deals_received: payload.deals.length,
      positions_received: payload.trades.length,
      duration_ms: Date.now() - startedAt,
      result: data,
    }));
    return NextResponse.json({ result: data });
  } catch (error) {
    console.error(JSON.stringify({ event: 'mt5_sync_ingest_failed', duration_ms: Date.now() - startedAt }));
    return apiError(error);
  }
}
