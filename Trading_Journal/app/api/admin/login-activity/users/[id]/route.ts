import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { requireAdmin } from '@/lib/server/admin-auth';
import { UnauthorizedError } from '@/lib/server/auth';
import { getUserActivitySummary } from '@/lib/server/login-activity';

export const dynamic = 'force-dynamic';

export async function GET(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    await requireAdmin(request);
    if (!z.string().uuid().safeParse(params.id).success) {
      return NextResponse.json({ error: 'Invalid user identifier.' }, { status: 400 });
    }
    const summary = await getUserActivitySummary(params.id);
    if (!summary) return NextResponse.json({ error: 'Activity profile not found.' }, { status: 404 });
    return NextResponse.json({ summary }, { headers: { 'Cache-Control': 'private, no-store' } });
  } catch (error) {
    if (error instanceof UnauthorizedError) {
      return NextResponse.json({ error: error.message }, { status: 403 });
    }
    console.error('Failed to load activity profile', error);
    return NextResponse.json({ error: 'Could not load activity profile.' }, { status: 500 });
  }
}
