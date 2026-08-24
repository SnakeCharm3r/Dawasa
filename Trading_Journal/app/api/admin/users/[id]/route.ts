import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { requireAdmin } from '@/lib/server/admin-auth';
import { UnauthorizedError } from '@/lib/server/auth';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';

export const dynamic = 'force-dynamic';

const updateSchema = z.object({
  is_active: z.boolean().optional(),
  role: z.enum(['admin', 'user']).optional(),
}).strict().refine((value) => value.is_active !== undefined || value.role !== undefined);

export async function PATCH(request: NextRequest, { params }: { params: { id: string } }) {
  try {
    const { user: currentAdmin } = await requireAdmin(request);
    const parsed = updateSchema.safeParse(await request.json());
    if (!parsed.success) {
      return NextResponse.json({ error: 'A valid active status or role is required.' }, { status: 400 });
    }

    const admin = getSupabaseAdminClient();
    const { data: targetData, error: targetError } = await admin.auth.admin.getUserById(params.id);
    if (targetError || !targetData.user) {
      return NextResponse.json({ error: 'User not found.' }, { status: 404 });
    }
    if (
      targetData.user.id === currentAdmin.id &&
      (parsed.data.is_active === false || parsed.data.role === 'user')
    ) {
      return NextResponse.json({ error: 'You cannot remove your own administrator access.' }, { status: 400 });
    }

    const { is_active, role } = parsed.data;
    if (is_active !== undefined) {
      const { error: profileError } = await admin
        .from('profiles')
        .update({ is_active, updated_at: new Date().toISOString() })
        .eq('id', params.id);
      if (profileError) throw profileError;
    }

    const { error: authError } = await admin.auth.admin.updateUserById(params.id, {
      ...(is_active === undefined ? {} : { ban_duration: is_active ? 'none' : '876000h' }),
      ...(role === undefined ? {} : {
        app_metadata: { ...targetData.user.app_metadata, role },
      }),
    });
    if (authError) {
      if (is_active !== undefined) {
        await admin.from('profiles').update({ is_active: !is_active }).eq('id', params.id);
      }
      throw authError;
    }

    return NextResponse.json({ id: params.id, is_active, role });
  } catch (error) {
    if (error instanceof UnauthorizedError) {
      return NextResponse.json({ error: error.message }, { status: 403 });
    }
    console.error('Failed to update admin user', error);
    return NextResponse.json({ error: 'Could not update this user.' }, { status: 500 });
  }
}
