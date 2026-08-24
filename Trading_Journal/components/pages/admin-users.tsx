'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import { ShieldCheck, UserCheck, UserX, Users } from 'lucide-react';
import { toast } from 'sonner';
import { invokeJournalApi } from '@/lib/edge-api';
import { getSupabaseClient } from '@/lib/supabase';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

type ManagedUser = {
  id: string;
  email: string | null;
  name: string | null;
  country: string | null;
  provider: string;
  created_at: string;
  last_login_at: string | null;
  is_active: boolean;
  is_admin: boolean;
  is_self: boolean;
};

const useSupabaseEdgeApi = process.env.NEXT_PUBLIC_USE_SUPABASE_EDGE_API === 'true';

function formatDate(value: string | null) {
  if (!value) return 'Never';
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
}

async function adminRequest(path: string, init?: RequestInit) {
  if (useSupabaseEdgeApi) {
    if (!init?.method || init.method === 'GET') {
      return invokeJournalApi<{ error?: string; users?: ManagedUser[] }>({ action: 'admin_list_users' });
    }
    const userId = path.split('/').filter(Boolean).at(-1);
    const changes = init.body ? JSON.parse(String(init.body)) as Record<string, unknown> : {};
    return invokeJournalApi<{ error?: string; users?: ManagedUser[] }>({
      action: 'admin_update_user', user_id: userId, ...changes,
    });
  }
  const { data } = await getSupabaseClient().auth.getSession();
  const token = data.session?.access_token;
  if (!token) throw new Error('Your session expired. Sign in again.');
  const response = await fetch(path, {
    ...init,
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
      ...init?.headers,
    },
    cache: 'no-store',
  });
  const payload = await response.json() as { error?: string; users?: ManagedUser[] };
  if (!response.ok) throw new Error(payload.error ?? 'The admin request failed.');
  return payload;
}

export function AdminUsersPage() {
  const [users, setUsers] = useState<ManagedUser[]>([]);
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [updatingId, setUpdatingId] = useState<string | null>(null);

  const loadUsers = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await adminRequest('/api/admin/users');
      setUsers(payload.users ?? []);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : 'Could not load users.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void loadUsers(); }, [loadUsers]);

  const filteredUsers = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) return users;
    return users.filter((user) =>
      [user.name, user.email, user.country, user.provider]
        .some((value) => value?.toLowerCase().includes(term))
    );
  }, [query, users]);

  const activeCount = users.filter((user) => user.is_active).length;

  const setActive = async (user: ManagedUser, isActive: boolean) => {
    setUpdatingId(user.id);
    try {
      await adminRequest(`/api/admin/users/${user.id}`, {
        method: 'PATCH',
        body: JSON.stringify({ is_active: isActive }),
      });
      setUsers((current) => current.map((item) =>
        item.id === user.id ? { ...item, is_active: isActive } : item
      ));
      toast.success(`${user.name ?? user.email ?? 'User'} is now ${isActive ? 'active' : 'inactive'}.`);
    } catch (updateError) {
      toast.error(updateError instanceof Error ? updateError.message : 'Could not update this user.');
    } finally {
      setUpdatingId(null);
    }
  };

  const setRole = async (user: ManagedUser, isAdmin: boolean) => {
    setUpdatingId(user.id);
    try {
      await adminRequest(`/api/admin/users/${user.id}`, {
        method: 'PATCH',
        body: JSON.stringify({ role: isAdmin ? 'admin' : 'user' }),
      });
      setUsers((current) => current.map((item) =>
        item.id === user.id ? { ...item, is_admin: isAdmin } : item
      ));
      toast.success(`${user.name ?? user.email ?? 'User'} is now ${isAdmin ? 'an administrator' : 'a standard user'}.`);
    } catch (updateError) {
      toast.error(updateError instanceof Error ? updateError.message : 'Could not update this role.');
    } finally {
      setUpdatingId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <div className="flex items-center gap-2 text-sm font-medium text-emerald-600">
          <ShieldCheck className="h-4 w-4" /> Administrator only
        </div>
        <h1 className="mt-1 text-2xl font-semibold tracking-tight">User management</h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Review registered users and control access. Authentication passwords are never available here.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <Card className="p-4">
          <Users className="h-5 w-5 text-muted-foreground" />
          <div className="mt-3 text-2xl font-semibold">{users.length}</div>
          <div className="text-xs text-muted-foreground">Registered accounts</div>
        </Card>
        <Card className="p-4">
          <UserCheck className="h-5 w-5 text-emerald-600" />
          <div className="mt-3 text-2xl font-semibold">{activeCount}</div>
          <div className="text-xs text-muted-foreground">Active accounts</div>
        </Card>
        <Card className="p-4">
          <UserX className="h-5 w-5 text-amber-600" />
          <div className="mt-3 text-2xl font-semibold">{users.length - activeCount}</div>
          <div className="text-xs text-muted-foreground">Inactive accounts</div>
        </Card>
      </div>

      <Card className="overflow-hidden">
        <div className="border-b p-4">
          <Input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search name, email, country, or provider"
            className="max-w-md"
          />
        </div>
        {loading ? (
          <div className="p-10 text-center text-sm text-muted-foreground">Loading users…</div>
        ) : error ? (
          <div className="p-10 text-center">
            <p className="text-sm text-destructive">{error}</p>
            <Button className="mt-4" variant="outline" onClick={() => void loadUsers()}>Try again</Button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Country</TableHead>
                  <TableHead>Signed up</TableHead>
                  <TableHead>Last login</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Controls</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredUsers.map((user) => (
                  <TableRow key={user.id}>
                    <TableCell>
                      <div className="font-medium">{user.name ?? 'Name not provided'}</div>
                      <div className="text-xs text-muted-foreground">{user.email ?? 'No email'} · {user.provider}</div>
                    </TableCell>
                    <TableCell>{user.country ?? 'Not provided'}</TableCell>
                    <TableCell className="whitespace-nowrap text-sm">{formatDate(user.created_at)}</TableCell>
                    <TableCell className="whitespace-nowrap text-sm">{formatDate(user.last_login_at)}</TableCell>
                    <TableCell className="text-right">
                      <Badge variant={user.is_admin ? 'default' : 'outline'}>
                        {user.is_admin ? 'Admin' : 'User'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={user.is_active ? 'default' : 'secondary'}>
                        {user.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex justify-end gap-2">
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={updatingId === user.id || user.is_self}
                          onClick={() => void setRole(user, !user.is_admin)}
                        >
                          {user.is_self ? 'Current admin' : user.is_admin ? 'Remove admin' : 'Make admin'}
                        </Button>
                        {!user.is_self && (
                          <Button
                            size="sm"
                            variant={user.is_active ? 'outline' : 'default'}
                            disabled={updatingId === user.id}
                            onClick={() => void setActive(user, !user.is_active)}
                          >
                            {updatingId === user.id ? 'Updating…' : user.is_active ? 'Deactivate' : 'Activate'}
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
                {!filteredUsers.length && (
                  <TableRow><TableCell colSpan={7} className="h-28 text-center text-muted-foreground">No users found.</TableCell></TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        )}
      </Card>
    </div>
  );
}
