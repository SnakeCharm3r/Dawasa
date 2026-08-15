import { NextRequest, NextResponse } from 'next/server';
import { tradingAccountInputSchema } from '@/lib/mt5/contracts';
import { encryptBrokerPassword } from '@/lib/server/broker-credentials';
import { requireUser } from '@/lib/server/auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';
import { publicTradingAccount } from '@/lib/server/trading-accounts';

export const dynamic = 'force-dynamic';

export async function GET(request: NextRequest) {
  try {
    const { user } = await requireUser(request);
    const admin = getSupabaseAdminClient();
    const { data, error } = await admin
      .from('trading_accounts')
      .select('*')
      .eq('user_id', user.id)
      .order('created_at', { ascending: false });
    if (error) throw error;

    const accounts = await Promise.all(
      (data ?? []).map(async (account) => {
        const { count } = await admin
          .from('trades')
          .select('id', { count: 'exact', head: true })
          .eq('user_id', user.id)
          .eq('trading_account_id', account.id)
          .eq('source', 'mt5');
        return publicTradingAccount({ ...account, imported_trade_count: count ?? 0 });
      })
    );
    return NextResponse.json({ accounts });
  } catch (error) {
    return apiError(error);
  }
}

export async function POST(request: NextRequest) {
  try {
    const { user } = await requireUser(request);
    const input = tradingAccountInputSchema.parse(await request.json());
    const historyStart = new Date(Date.now() - input.history_days * 86400000).toISOString();
    const { history_days: _historyDays, password, ...metadata } = input;
    const { data, error } = await getSupabaseAdminClient()
      .from('trading_accounts')
      .upsert(
        {
          ...metadata,
          user_id: user.id,
          platform: 'MT5',
          broker_password_encrypted: encryptBrokerPassword(password),
          history_start_at: historyStart,
          sync_status: 'disconnected',
          updated_at: new Date().toISOString(),
        },
        { onConflict: 'user_id,broker,platform,account_number,server' }
      )
      .select('*')
      .single();
    if (error) throw error;
    console.info(JSON.stringify({ event: 'mt5_account_registered', user_id: user.id, account_id: data.id }));
    return NextResponse.json({ account: publicTradingAccount(data) }, { status: 201 });
  } catch (error) {
    return apiError(error);
  }
}
