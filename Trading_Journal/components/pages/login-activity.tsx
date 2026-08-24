'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { CalendarClock, ChevronLeft, ChevronRight, MapPin, MonitorSmartphone, Search, ShieldAlert } from 'lucide-react';
import { invokeJournalApi } from '@/lib/edge-api';
import { getSupabaseClient } from '@/lib/supabase';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

type Activity = {
  id: string;
  user_id: string | null;
  attempted_email: string | null;
  event_type: 'registered' | 'login' | 'logout' | 'failed_login';
  ip_address: string | null;
  country: string | null;
  city: string | null;
  timezone: string | null;
  location_source: string | null;
  user_agent: string | null;
  browser: string | null;
  operating_system: string | null;
  device_type: string | null;
  success: boolean;
  failure_reason: string | null;
  created_at: string;
  email: string | null;
  display_name: string | null;
};

type Summary = {
  user: {
    email: string | null;
    display_name: string | null;
    registered_ip: string | null;
    registered_country: string | null;
    registered_city: string | null;
    registered_at: string | null;
  };
  lastLogin: {
    created_at: string;
    ip_address: string | null;
    country: string | null;
    city: string | null;
    browser: string | null;
    operating_system: string | null;
    device_type: string | null;
  } | null;
  recentDevices: Array<{
    browser: string | null;
    operating_system: string | null;
    device_type: string | null;
    last_seen: string;
  }>;
};

const useSupabaseEdgeApi = process.env.NEXT_PUBLIC_USE_SUPABASE_EDGE_API === 'true';

async function adminGet<T>(path: string) {
  if (useSupabaseEdgeApi) {
    const url = new URL(path, 'https://local.invalid');
    const userMatch = url.pathname.match(/\/users\/([0-9a-f-]+)$/iu);
    if (userMatch) {
      return invokeJournalApi<T>({ action: 'admin_user_activity', user_id: userMatch[1] });
    }
    const page = Number(url.searchParams.get('page') ?? 1);
    const pageSize = Number(url.searchParams.get('page_size') ?? 25);
    return invokeJournalApi<T>({
      action: 'admin_list_activity',
      page,
      page_size: pageSize,
      ...(url.searchParams.get('search') ? { search: url.searchParams.get('search') } : {}),
      ...(url.searchParams.get('event_type') ? { event_type: url.searchParams.get('event_type') } : {}),
      ...(url.searchParams.get('date_from') ? { date_from: url.searchParams.get('date_from') } : {}),
      ...(url.searchParams.get('date_to') ? { date_to: url.searchParams.get('date_to') } : {}),
    });
  }
  const { data } = await getSupabaseClient().auth.getSession();
  if (!data.session) throw new Error('Your session expired. Sign in again.');
  const response = await fetch(path, {
    headers: { Authorization: `Bearer ${data.session.access_token}` },
    cache: 'no-store',
  });
  const payload = await response.json() as T & { error?: string };
  if (!response.ok) throw new Error(payload.error ?? 'The admin request failed.');
  return payload;
}

function dateTime(value: string | null) {
  if (!value) return 'Not available';
  return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function location(activity: Pick<Activity, 'city' | 'country'>) {
  return [activity.city, activity.country].filter(Boolean).join(', ') || 'Unknown';
}

export function LoginActivityPage() {
  const [activities, setActivities] = useState<Activity[]>([]);
  const [search, setSearch] = useState('');
  const [eventType, setEventType] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selected, setSelected] = useState<Activity | null>(null);
  const [summary, setSummary] = useState<Summary | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = new URLSearchParams({ page: String(page), page_size: '25' });
      if (search.trim()) params.set('search', search.trim());
      if (eventType) params.set('event_type', eventType);
      if (dateFrom) params.set('date_from', dateFrom);
      if (dateTo) params.set('date_to', dateTo);
      const data = await adminGet<{ activities: Activity[]; pagination: { total: number; total_pages: number } }>(`/api/admin/login-activity?${params}`);
      setActivities(data.activities);
      setTotal(data.pagination.total);
      setTotalPages(data.pagination.total_pages);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : 'Could not load activity.');
    } finally {
      setLoading(false);
    }
  }, [dateFrom, dateTo, eventType, page, search]);

  useEffect(() => { void load(); }, [load]);

  const applyFilters = (event: FormEvent) => {
    event.preventDefault();
    if (page === 1) void load();
    else setPage(1);
  };

  const showDetails = async (activity: Activity) => {
    setSelected(activity);
    setSummary(null);
    if (!activity.user_id) return;
    try {
      const data = await adminGet<{ summary: Summary }>(`/api/admin/login-activity/users/${activity.user_id}`);
      setSummary(data.summary);
    } catch {
      // The event detail remains available if its profile summary cannot load.
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <div className="flex items-center gap-2 text-sm font-medium text-emerald-600">
          <ShieldAlert className="h-4 w-4" /> Administrator only
        </div>
        <h1 className="mt-1 text-2xl font-semibold tracking-tight">Login Activity</h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Registration and authentication events. Locations are approximate and based on IP address.
        </p>
      </div>

      <Card className="p-4">
        <form className="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_180px_160px_160px_auto]" onSubmit={applyFilters}>
          <div className="relative">
            <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Name, email, or IP" className="pl-9" />
          </div>
          <select value={eventType} onChange={(e) => setEventType(e.target.value)} className="h-10 rounded-md border border-input bg-background px-3 text-sm">
            <option value="">All event types</option>
            <option value="registered">Registered</option>
            <option value="login">Login</option>
            <option value="logout">Logout</option>
            <option value="failed_login">Failed login</option>
          </select>
          <Input type="date" aria-label="From date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
          <Input type="date" aria-label="To date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
          <Button type="submit">Apply filters</Button>
        </form>
      </Card>

      <Card className="overflow-hidden">
        <div className="flex items-center justify-between border-b px-4 py-3 text-sm">
          <span className="text-muted-foreground">{total} events</span>
          <span className="text-muted-foreground">Page {page} of {totalPages}</span>
        </div>
        {loading ? (
          <div className="p-12 text-center text-sm text-muted-foreground">Loading login activity…</div>
        ) : error ? (
          <div className="p-12 text-center text-sm text-destructive">{error}</div>
        ) : (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader><TableRow>
                <TableHead>User</TableHead><TableHead>Event</TableHead><TableHead>IP address</TableHead>
                <TableHead>Approximate location</TableHead><TableHead>Device</TableHead><TableHead>Date and time</TableHead>
              </TableRow></TableHeader>
              <TableBody>
                {activities.map((activity) => (
                  <TableRow key={activity.id} className="cursor-pointer" onClick={() => void showDetails(activity)}>
                    <TableCell><div className="font-medium">{activity.display_name ?? 'Unknown user'}</div><div className="text-xs text-muted-foreground">{activity.email ?? activity.attempted_email ?? 'No email'}</div></TableCell>
                    <TableCell><Badge variant={activity.success ? 'default' : 'destructive'}>{activity.event_type.replace('_', ' ')}</Badge></TableCell>
                    <TableCell className="font-mono text-xs">{activity.ip_address ?? 'Unknown'}</TableCell>
                    <TableCell>{location(activity)}</TableCell>
                    <TableCell><div>{activity.browser ?? 'Unknown'}</div><div className="text-xs text-muted-foreground">{activity.operating_system ?? 'Unknown'} · {activity.device_type ?? 'Unknown'}</div></TableCell>
                    <TableCell className="whitespace-nowrap">{dateTime(activity.created_at)}</TableCell>
                  </TableRow>
                ))}
                {!activities.length && <TableRow><TableCell colSpan={6} className="h-28 text-center text-muted-foreground">No activity matches these filters.</TableCell></TableRow>}
              </TableBody>
            </Table>
          </div>
        )}
        <div className="flex justify-end gap-2 border-t p-4">
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((current) => current - 1)}><ChevronLeft className="mr-1 h-4 w-4" /> Previous</Button>
          <Button variant="outline" size="sm" disabled={page >= totalPages} onClick={() => setPage((current) => current + 1)}>Next <ChevronRight className="ml-1 h-4 w-4" /></Button>
        </div>
      </Card>

      <Dialog open={Boolean(selected)} onOpenChange={(open) => { if (!open) setSelected(null); }}>
        <DialogContent className="max-h-[85vh] max-w-2xl overflow-y-auto">
          <DialogHeader><DialogTitle>Login activity details</DialogTitle><DialogDescription>Approximate location based on IP address—not a precise physical address.</DialogDescription></DialogHeader>
          {selected && <div className="space-y-5 text-sm">
            <div className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2">
              <div><div className="text-xs text-muted-foreground">Event</div><div className="font-medium capitalize">{selected.event_type.replace('_', ' ')}</div></div>
              <div><div className="text-xs text-muted-foreground">Date and time</div><div>{dateTime(selected.created_at)}</div></div>
              <div><div className="text-xs text-muted-foreground">IP address</div><div className="font-mono">{selected.ip_address ?? 'Unknown'}</div></div>
              <div><div className="text-xs text-muted-foreground">Location</div><div>{location(selected)}</div></div>
              <div><div className="text-xs text-muted-foreground">Timezone</div><div>{selected.timezone ?? 'Unknown'}</div></div>
              <div><div className="text-xs text-muted-foreground">Source</div><div>{selected.location_source ?? 'Not available'}</div></div>
              <div className="sm:col-span-2"><div className="text-xs text-muted-foreground">Device</div><div>{selected.browser} · {selected.operating_system} · {selected.device_type}</div></div>
              <div className="sm:col-span-2 break-all"><div className="text-xs text-muted-foreground">Full user agent</div><div>{selected.user_agent ?? 'Not available'}</div></div>
              {!selected.success && <div className="sm:col-span-2"><div className="text-xs text-muted-foreground">Failure reason</div><div>{selected.failure_reason ?? 'Authentication failed'}</div></div>}
            </div>
            {summary && <div>
              <h3 className="font-semibold">User activity summary</h3>
              <div className="mt-3 grid gap-3 sm:grid-cols-2">
                <Card className="p-4"><MapPin className="h-4 w-4 text-muted-foreground" /><div className="mt-2 text-xs text-muted-foreground">Registration source</div><div>{[summary.user.registered_city, summary.user.registered_country].filter(Boolean).join(', ') || 'Unknown'}</div><div className="font-mono text-xs">{summary.user.registered_ip ?? 'Unknown IP'}</div></Card>
                <Card className="p-4"><CalendarClock className="h-4 w-4 text-muted-foreground" /><div className="mt-2 text-xs text-muted-foreground">Last successful login</div><div>{dateTime(summary.lastLogin?.created_at ?? null)}</div><div className="font-mono text-xs">{summary.lastLogin?.ip_address ?? 'Unknown IP'}</div></Card>
              </div>
              <div className="mt-3 rounded-lg border p-4"><div className="flex items-center gap-2 font-medium"><MonitorSmartphone className="h-4 w-4" /> Recent devices</div><div className="mt-2 space-y-2">{summary.recentDevices.map((device, index) => <div key={`${device.browser}-${device.operating_system}-${index}`} className="flex justify-between text-xs"><span>{device.browser} · {device.operating_system} · {device.device_type}</span><span className="text-muted-foreground">{dateTime(device.last_seen)}</span></div>)}</div></div>
            </div>}
          </div>}
        </DialogContent>
      </Dialog>
    </div>
  );
}
