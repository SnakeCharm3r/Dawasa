import { NextRequest, NextResponse } from 'next/server';
import { requireUser, UnauthorizedError } from '@/lib/server/auth';
import { recordLoginActivity } from '@/lib/server/login-activity';

export const dynamic = 'force-dynamic';

export async function POST(request: NextRequest) {
  try {
    const { user } = await requireUser(request);
    await recordLoginActivity({ eventType: 'login', request, user, success: true });
    return NextResponse.json({ recorded: true }, { headers: { 'Cache-Control': 'private, no-store' } });
  } catch (error) {
    if (error instanceof UnauthorizedError) {
      return NextResponse.json({ error: 'Authentication is required.' }, { status: 401 });
    }
    console.error('OAuth login audit logging failed', error);
    return NextResponse.json({ error: 'Could not record login activity.' }, { status: 500 });
  }
}
