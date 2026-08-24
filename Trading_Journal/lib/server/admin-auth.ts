import type { NextRequest } from 'next/server';
import type { User } from '@supabase/supabase-js';
import { requireUser, UnauthorizedError } from './auth';

export function isAdminUser(user: Pick<User, 'app_metadata'>) {
  return user.app_metadata?.role === 'admin';
}

export async function requireAdmin(request: NextRequest) {
  const authenticated = await requireUser(request);
  if (!isAdminUser(authenticated.user)) {
    throw new UnauthorizedError('Administrator access is required.');
  }

  return authenticated;
}
