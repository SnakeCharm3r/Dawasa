import { NextRequest, NextResponse } from 'next/server';
import type { User } from '@supabase/supabase-js';
import { requireAdmin } from '@/lib/server/admin-auth';
import { UnauthorizedError } from '@/lib/server/auth';
import { getSupabaseAdminClient } from '@/lib/server/supabase-admin';

export const dynamic = 'force-dynamic';

type AdminProfile = {
  id: string;
  email: string | null;
  username: string | null;
  display_name: string | null;
  country: string | null;
  is_active: boolean;
  created_at: string;
};

export async function GET(request: NextRequest) {
  try {
    const { user: currentAdmin } = await requireAdmin(request);
    const admin = getSupabaseAdminClient();
    const authUsers: User[] = [];
    const perPage = 1000;

    for (let page = 1; ; page += 1) {
      const { data, error } = await admin.auth.admin.listUsers({ page, perPage });
      if (error) throw error;
      authUsers.push(...data.users);
      if (data.users.length < perPage) break;
    }

    const ids = authUsers.map((user) => user.id);
    const profiles: AdminProfile[] = [];
    for (let start = 0; start < ids.length; start += 200) {
      const { data, error } = await admin
        .from('profiles')
        .select('id,email,username,display_name,country,is_active,created_at')
        .in('id', ids.slice(start, start + 200));
      if (error) throw error;
      profiles.push(...((data ?? []) as AdminProfile[]));
    }

    const profileById = new Map(profiles.map((profile) => [profile.id, profile]));
    const users = authUsers.map((user) => {
      const profile = profileById.get(user.id);
      return {
        id: user.id,
        email: user.email ?? profile?.email ?? null,
        name: profile?.display_name ?? profile?.username ?? user.user_metadata?.full_name ?? null,
        country: profile?.country ?? user.user_metadata?.country ?? null,
        provider: user.app_metadata?.provider ?? 'email',
        created_at: user.created_at,
        last_login_at: user.last_sign_in_at ?? null,
        is_active: profile?.is_active ?? true,
        is_admin: user.app_metadata?.role === 'admin',
        is_self: user.id === currentAdmin.id,
      };
    });

    return NextResponse.json(
      { users },
      { headers: { 'Cache-Control': 'no-store' } }
    );
  } catch (error) {
    if (error instanceof UnauthorizedError) {
      return NextResponse.json({ error: error.message }, { status: 403 });
    }
    console.error('Failed to list admin users', error);
    return NextResponse.json({ error: 'Could not load users.' }, { status: 500 });
  }
}
