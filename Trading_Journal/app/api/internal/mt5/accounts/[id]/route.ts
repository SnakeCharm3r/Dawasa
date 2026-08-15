import { NextRequest, NextResponse } from 'next/server';
import { requireConnectorToken } from '@/lib/server/internal-auth';
import { apiError } from '@/lib/server/responses';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';

export const dynamic = 'force-dynamic';

export async function GET(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireConnectorToken(request, params.id);
    const { data, error } = await getSupabaseAdminClient()
      .from('trading_accounts')
      .select('id,broker,platform,account_number,server,history_start_at,last_deal_time,last_deal_ticket,sync_status,sync_requested_at')
      .eq('id', params.id)
      .maybeSingle();
    if (error) throw error;
    if (!data) return NextResponse.json({ error: 'Trading account not found.' }, { status: 404 });
    return NextResponse.json({ account: data });
  } catch (error) {
    return apiError(error);
  }
}
