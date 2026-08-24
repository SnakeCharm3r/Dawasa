import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { getServerAuthClient } from '@/lib/server/supabase-admin';
import {
  findActivityUserByEmail,
  recordLoginActivity,
} from '@/lib/server/login-activity';

export const dynamic = 'force-dynamic';

const loginSchema = z.object({
  email: z.string().trim().email().max(320),
  password: z.string().min(1).max(256),
}).strict();

async function resolveFailedUser(email: string) {
  return (await findActivityUserByEmail(email))?.id ?? null;
}

export async function POST(request: NextRequest) {
  let raw: unknown;
  try {
    raw = await request.json();
  } catch {
    return NextResponse.json({ error: 'Invalid email or password.' }, { status: 401 });
  }
  const parsed = loginSchema.safeParse(raw);
  if (!parsed.success) {
    return NextResponse.json({ error: 'Invalid email or password.' }, { status: 401 });
  }

  const email = parsed.data.email.toLowerCase();
  const { data, error } = await getServerAuthClient().auth.signInWithPassword({
    email,
    password: parsed.data.password,
  });

  if (error || !data.user || !data.session) {
    try {
      const userId = await resolveFailedUser(email);
      await recordLoginActivity({
        eventType: 'failed_login',
        request,
        user: userId ? ({ id: userId, email } as never) : null,
        attemptedEmail: email,
        success: false,
        failureReason: error?.code ?? 'invalid_credentials',
      });
    } catch (activityError) {
      console.error('Failed-login audit logging failed', activityError);
    }
    return NextResponse.json(
      { error: 'Invalid email or password.' },
      { status: 401, headers: { 'Cache-Control': 'private, no-store' } }
    );
  }

  try {
    await recordLoginActivity({ eventType: 'login', request, user: data.user, success: true });
  } catch (activityError) {
    console.error('Login audit logging failed', activityError);
    return NextResponse.json({ error: 'Could not complete sign in.' }, { status: 503 });
  }

  return NextResponse.json(
    { access_token: data.session.access_token, refresh_token: data.session.refresh_token },
    { headers: { 'Cache-Control': 'private, no-store' } }
  );
}
