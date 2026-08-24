import { randomUUID } from 'node:crypto';
import { execFile } from 'node:child_process';
import path from 'node:path';
import type { User } from '@supabase/supabase-js';
import { classifyIp, requestDeviceContext } from './request-context';
import { getSupabaseAdminClient } from './supabase-admin';

export type ActivityEventType = 'registered' | 'login' | 'logout' | 'failed_login';
type RequestLike = Parameters<typeof requestDeviceContext>[0];

type RecordActivityInput = {
  eventType: ActivityEventType;
  request: RequestLike;
  user?: User | null;
  attemptedEmail?: string | null;
  success: boolean;
  failureReason?: string | null;
};

export type ActivityFilters = {
  search?: string;
  eventType?: ActivityEventType;
  dateFrom?: string;
  dateTo?: string;
  page: number;
  pageSize: number;
};

function userName(user: User) {
  return user.user_metadata?.full_name ?? user.user_metadata?.name ?? null;
}

export async function upsertActivityUser(user: User) {
  const { error } = await getSupabaseAdminClient().from('profiles').upsert(
    {
      id: user.id,
      email: user.email ?? null,
      display_name: userName(user),
      updated_at: new Date().toISOString(),
    },
    { onConflict: 'id' }
  );
  if (error) throw error;
}

export async function findActivityUserByEmail(email: string) {
  const { data, error } = await getSupabaseAdminClient()
    .from('profiles')
    .select('id,email,display_name')
    .ilike('email', email.trim())
    .maybeSingle();
  if (error) throw error;
  return data;
}

function updateLocationAsync(
  activityId: string,
  userId: string | null,
  eventType: ActivityEventType,
  ipAddress: string | null
) {
  if (!ipAddress) return;
  const script = path.join(process.cwd(), 'scripts', 'geolocate_ip.py');
  const python = process.env.PYTHON_BIN ?? 'python3';
  execFile(python, [script, ipAddress], { timeout: 6_000, maxBuffer: 32_768 }, async (error, stdout) => {
    if (error) {
      console.warn(JSON.stringify({ event: 'ip_geolocation_failed', activity_id: activityId, message: error.message }));
      return;
    }
    try {
      const location = JSON.parse(stdout) as {
        country?: string | null;
        city?: string | null;
        timezone?: string | null;
        source?: string | null;
      };
      const admin = getSupabaseAdminClient();
      const { error: logError } = await admin.from('login_activity_logs').update({
        country: location.country ?? null,
        city: location.city ?? null,
        timezone: location.timezone ?? null,
        location_source: location.source ?? null,
        updated_at: new Date().toISOString(),
      }).eq('id', activityId);
      if (logError) throw logError;

      if (userId && eventType === 'registered') {
        const { error: profileError } = await admin.from('profiles').update({
          registered_country: location.country ?? null,
          registered_city: location.city ?? null,
          updated_at: new Date().toISOString(),
        }).eq('id', userId);
        if (profileError) throw profileError;
      }
    } catch (locationError) {
      console.warn(JSON.stringify({
        event: 'ip_geolocation_update_failed',
        activity_id: activityId,
        message: locationError instanceof Error ? locationError.message : 'Unknown location update error',
      }));
    }
  });
}

export async function recordLoginActivity(input: RecordActivityInput) {
  const context = requestDeviceContext(input.request);
  const activityId = randomUUID();
  const userId = input.user?.id ?? null;
  if (input.user && input.eventType !== 'failed_login') await upsertActivityUser(input.user);

  const kind = context.ipAddress ? classifyIp(context.ipAddress) : 'unknown';
  const immediateCountry = kind === 'local' ? 'Local Network' : kind === 'internal' ? 'Internal Network' : null;
  const locationSource = kind === 'local' ? 'local' : kind === 'internal' ? 'internal' : null;
  const now = new Date().toISOString();
  const admin = getSupabaseAdminClient();
  const { error } = await admin.from('login_activity_logs').insert({
    id: activityId,
    user_id: userId,
    attempted_email: input.attemptedEmail?.trim().toLowerCase() ?? input.user?.email?.toLowerCase() ?? null,
    event_type: input.eventType,
    ip_address: context.ipAddress,
    country: immediateCountry,
    location_source: locationSource,
    user_agent: context.userAgent,
    browser: context.browser,
    operating_system: context.operatingSystem,
    device_type: context.deviceType,
    success: input.success,
    failure_reason: input.failureReason?.slice(0, 255) ?? null,
    created_at: now,
    updated_at: now,
  });
  if (error) throw error;

  if (input.user && input.eventType === 'registered') {
    const { error: profileError } = await admin.from('profiles').update({
      registered_ip: context.ipAddress,
      registered_country: immediateCountry,
      registered_at: now,
      updated_at: now,
    }).eq('id', input.user.id);
    if (profileError) throw profileError;
  }

  updateLocationAsync(activityId, userId, input.eventType, context.ipAddress);
  return activityId;
}

function safeSearchTerm(value: string) {
  return value.trim().replace(/[,%()'"\\]/g, '').slice(0, 200);
}

export async function listLoginActivity(filters: ActivityFilters) {
  const offset = (filters.page - 1) * filters.pageSize;
  let query = getSupabaseAdminClient()
    .from('login_activity_admin')
    .select('*', { count: 'exact' })
    .order('created_at', { ascending: false })
    .order('id', { ascending: false })
    .range(offset, offset + filters.pageSize - 1);

  if (filters.search) {
    const term = safeSearchTerm(filters.search);
    if (term) query = query.or(
      `display_name.ilike.%${term}%,email.ilike.%${term}%,attempted_email.ilike.%${term}%,ip_address.ilike.%${term}%`
    );
  }
  if (filters.eventType) query = query.eq('event_type', filters.eventType);
  if (filters.dateFrom) query = query.gte('created_at', `${filters.dateFrom}T00:00:00.000Z`);
  if (filters.dateTo) {
    const exclusiveEnd = new Date(`${filters.dateTo}T00:00:00.000Z`);
    exclusiveEnd.setUTCDate(exclusiveEnd.getUTCDate() + 1);
    query = query.lt('created_at', exclusiveEnd.toISOString());
  }

  const { data, count, error } = await query;
  if (error) throw error;
  return { rows: data ?? [], total: count ?? 0 };
}

export async function getUserActivitySummary(userId: string) {
  const admin = getSupabaseAdminClient();
  const { data: user, error: userError } = await admin
    .from('profiles')
    .select('id,email,display_name,registered_ip,registered_country,registered_city,registered_at')
    .eq('id', userId)
    .maybeSingle();
  if (userError) throw userError;
  if (!user) return null;

  const { data: lastLogins, error: loginError } = await admin
    .from('login_activity_admin')
    .select('created_at,ip_address,country,city,timezone,browser,operating_system,device_type')
    .eq('user_id', userId)
    .eq('event_type', 'login')
    .eq('success', true)
    .order('created_at', { ascending: false })
    .limit(1);
  if (loginError) throw loginError;

  const { data: activity, error: devicesError } = await admin
    .from('login_activity_admin')
    .select('browser,operating_system,device_type,created_at')
    .eq('user_id', userId)
    .eq('success', true)
    .order('created_at', { ascending: false })
    .limit(100);
  if (devicesError) throw devicesError;

  const seen = new Set<string>();
  const recentDevices = (activity ?? []).flatMap((row) => {
    const key = `${row.browser ?? ''}|${row.operating_system ?? ''}|${row.device_type ?? ''}`;
    if (seen.has(key) || seen.size >= 5) return [];
    seen.add(key);
    return [{
      browser: row.browser,
      operating_system: row.operating_system,
      device_type: row.device_type,
      last_seen: row.created_at,
    }];
  });

  return { user, lastLogin: lastLogins?.[0] ?? null, recentDevices };
}
