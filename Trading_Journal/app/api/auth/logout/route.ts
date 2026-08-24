import { NextRequest, NextResponse } from 'next/server';
import { requireUser, UnauthorizedError } from '@/lib/server/auth';
import { recordLoginActivity } from '@/lib/server/login-activity';

export const dynamic = 'force-dynamic';

export async function POST(request: NextRequest) {
  try {
    const { user } = await requireUser(request);
    await recordLoginActivity({ eventType: 'logout', request, user, success: true });
    return NextResponse.json({ recorded: true }, { headers: { 'Cache-Control': 'private, no-store' } });
  } catch (error) {
    if (error instanceof UnauthorizedError) {
      return NextResponse.json({ error: 'Authentication is required.' }, { status: 401 });
    }
    console.error('Logout audit logging failed', error);
    return NextResponse.json({ error: 'Could not record logout activity.' }, { status: 500 });
  }
}
