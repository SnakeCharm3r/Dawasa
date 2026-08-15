import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';
import { registrationClientKey, takeRegistrationAttempt } from '@/lib/server/registration-rate-limit';

export const dynamic = 'force-dynamic';

const registrationSchema = z.object({
  name: z.string().trim().min(2).max(120),
  email: z.string().trim().email().max(320),
  password: z.string().min(8).max(256),
});

export async function POST(request: NextRequest) {
  const limit = takeRegistrationAttempt(registrationClientKey(request.headers));
  if (!limit.allowed) {
    return NextResponse.json(
      { error: 'Too many account creation attempts. Please try again later.' },
      {
        status: 429,
        headers: { 'Retry-After': String(Math.ceil((limit.resetsAt - Date.now()) / 1000)) },
      }
    );
  }

  const parsed = registrationSchema.safeParse(await request.json());
  if (!parsed.success) {
    return NextResponse.json({ error: 'Enter a valid name, email, and password of at least 8 characters.' }, { status: 400 });
  }

  const { name, email, password } = parsed.data;
  const { error } = await getSupabaseAdminClient().auth.admin.createUser({
    email,
    password,
    email_confirm: true,
    user_metadata: { full_name: name },
  });

  if (error) {
    const duplicate = error.message.toLowerCase().includes('already') || error.status === 422;
    return NextResponse.json(
      { error: duplicate ? 'An account with this email already exists. Sign in instead.' : 'Could not create your account.' },
      { status: duplicate ? 409 : 400 }
    );
  }

  return NextResponse.json(
    { created: true },
    { status: 201, headers: { 'Cache-Control': 'no-store' } }
  );
}
