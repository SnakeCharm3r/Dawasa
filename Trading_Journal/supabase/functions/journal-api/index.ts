import { createClient, type SupabaseClient, type User } from 'npm:@supabase/supabase-js@2.58.0';
import { z } from 'npm:zod@3.23.8';

const ALLOWED_ORIGINS = new Set([
  'https://milanolodge.gt.tc',
  'http://localhost:3000',
  'http://127.0.0.1:3000',
]);
const BROKERS = [
  'Exness', 'IC Markets', 'Pepperstone', 'XM', 'HFM',
  'FXTM', 'FP Markets', 'Tickmill', 'Admirals', 'Other',
] as const;
const PAIRING_WINDOW_MS = 10 * 60_000;

class HttpError extends Error {
  constructor(public status: number, message: string, public retryAfter?: number) {
    super(message);
  }
}

function corsHeaders(request: Request) {
  const origin = request.headers.get('origin');
  const headers: Record<string, string> = {
    'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Cache-Control': 'no-store',
    Vary: 'Origin',
  };
  if (origin && ALLOWED_ORIGINS.has(origin)) headers['Access-Control-Allow-Origin'] = origin;
  return headers;
}

function json(request: Request, body: unknown, status = 200, extraHeaders: Record<string, string> = {}) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...corsHeaders(request), ...extraHeaders, 'Content-Type': 'application/json; charset=utf-8' },
  });
}

function serviceKey() {
  const secretKeys = Deno.env.get('SUPABASE_SECRET_KEYS');
  if (secretKeys) {
    try {
      const parsed = JSON.parse(secretKeys) as { default?: string };
      if (parsed.default) return parsed.default;
    } catch {
      // Fall through to legacy keys while projects transition to secret keys.
    }
  }
  const key = Deno.env.get('SUPABASE_SECRET_KEY') || Deno.env.get('SUPABASE_SERVICE_ROLE_KEY');
  if (!key) throw new Error('Supabase service key is not configured.');
  return key;
}

function adminClient() {
  const url = Deno.env.get('SUPABASE_URL');
  if (!url) throw new Error('SUPABASE_URL is not configured.');
  return createClient(url, serviceKey(), {
    auth: { autoRefreshToken: false, persistSession: false },
  });
}

function bearerToken(request: Request) {
  const authorization = request.headers.get('authorization') ?? '';
  const match = authorization.match(/^Bearer\s+(.+)$/i);
  if (!match?.[1]) throw new HttpError(401, 'Authentication is required.');
  return match[1].trim();
}

async function requireUser(request: Request, admin: SupabaseClient): Promise<User> {
  const token = bearerToken(request);
  const { data, error } = await admin.auth.getUser(token);
  if (error || !data.user) throw new HttpError(401, 'Your session expired. Sign in again.');
  return data.user;
}

async function sha256Hex(value: string) {
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(value));
  return [...new Uint8Array(digest)].map((byte) => byte.toString(16).padStart(2, '0')).join('');
}

function base64Url(bytes: Uint8Array) {
  let binary = '';
  for (const byte of bytes) binary += String.fromCharCode(byte);
  return btoa(binary).replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/u, '');
}

function randomSecret() {
  return base64Url(crypto.getRandomValues(new Uint8Array(32)));
}

async function encryptBrokerPassword(password: string) {
  const keyMaterial = new TextEncoder().encode(`trading-journal:broker-credentials:v1\0${serviceKey()}`);
  const keyDigest = await crypto.subtle.digest('SHA-256', keyMaterial);
  const key = await crypto.subtle.importKey('raw', keyDigest, 'AES-GCM', false, ['encrypt']);
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const encryptedWithTag = new Uint8Array(await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv, tagLength: 128 },
    key,
    new TextEncoder().encode(password),
  ));
  const ciphertext = encryptedWithTag.slice(0, -16);
  const tag = encryptedWithTag.slice(-16);
  return `v1.${base64Url(iv)}.${base64Url(tag)}.${base64Url(ciphertext)}`;
}

function maskAccountNumber(value: string) {
  if (value.length <= 4) return '*'.repeat(value.length);
  return `${'*'.repeat(Math.min(8, value.length - 4))}${value.slice(-4)}`;
}

function publicAccount(account: Record<string, unknown>) {
  const safe = { ...account };
  delete safe.connector_token_hash;
  delete safe.pairing_code_hash;
  delete safe.broker_password_encrypted;
  if (typeof safe.account_number === 'string') safe.account_number = maskAccountNumber(safe.account_number);
  return safe;
}

const registrationSchema = z.object({
  action: z.literal('register'),
  name: z.string().trim().min(2).max(120),
  email: z.string().trim().email().max(320),
  password: z.string().min(8).max(256),
}).strict();

const accountInputSchema = z.object({
  action: z.literal('create_account'),
  broker: z.enum(BROKERS),
  account_number: z.string().trim().min(1).max(40),
  password: z.string().min(1).max(256),
  server: z.string().trim().min(1).max(120),
  account_name: z.string().trim().max(120).nullable().optional(),
  account_currency: z.string().trim().max(12).nullable().optional(),
  account_type: z.string().trim().max(60).nullable().optional(),
  history_days: z.union([z.literal(30), z.literal(90), z.literal(180), z.literal(365)]).default(90),
}).strict();

const accountActionSchema = z.object({
  action: z.enum(['get_account', 'disconnect_account', 'pair_connector', 'request_sync']),
  account_id: z.string().uuid(),
}).strict();

const resyncSchema = z.object({
  action: z.literal('request_resync'),
  account_id: z.string().uuid(),
  days: z.union([z.literal(30), z.literal(90), z.literal(180), z.literal(365)]),
}).strict();

const optionalNumber = z.number().finite().nullable().optional();
const optionalText = z.string().max(500).nullable().optional();
const normalizedDealSchema = z.object({
  broker_deal_id: z.string().min(1),
  broker_order_id: z.string().nullable().optional(),
  broker_position_id: z.string().min(1),
  entry_type: z.enum(['in', 'out', 'inout', 'out_by']),
  deal_type: z.string().min(1),
  symbol: z.string().min(1),
  side: z.enum(['buy', 'sell']).nullable().optional(),
  deal_time: z.string().datetime({ offset: true }),
  price: z.number().finite().nonnegative(),
  volume: z.number().finite().nonnegative(),
  profit: z.number().finite().default(0),
  commission: z.number().finite().default(0),
  swap: z.number().finite().default(0),
  fee: z.number().finite().default(0),
  magic_number: z.number().int().nullable().optional(),
  comment: optionalText,
  raw_metadata: z.record(z.unknown()).default({}),
});
const normalizedTradeSchema = z.object({
  broker_position_id: z.string().min(1),
  broker_order_id: z.string().nullable().optional(),
  broker_deal_id: z.string().nullable().optional(),
  broker_deal_ids: z.array(z.string().min(1)).min(2),
  symbol: z.string().min(1),
  side: z.enum(['buy', 'sell']),
  volume: z.number().finite().positive(),
  entry_price: z.number().finite().nonnegative(),
  exit_price: z.number().finite().nonnegative(),
  open_time: z.string().datetime({ offset: true }),
  close_time: z.string().datetime({ offset: true }),
  stop_loss: optionalNumber,
  take_profit: optionalNumber,
  commission: z.number().finite().default(0),
  swap: z.number().finite().default(0),
  fee: z.number().finite().default(0),
  gross_profit: z.number().finite(),
  net_profit: z.number().finite(),
  magic_number: z.number().int().nullable().optional(),
  comment: optionalText,
  raw_metadata: z.record(z.unknown()).default({}),
});
const mt5PayloadSchema = z.object({
  trading_account_id: z.string().uuid(),
  account: z.object({
    login: z.string().optional(),
    name: optionalText,
    server: optionalText,
    currency: z.string().max(12).nullable().optional(),
    leverage: z.number().int().positive().nullable().optional(),
    balance: optionalNumber,
    equity: optionalNumber,
    margin: optionalNumber,
    free_margin: optionalNumber,
    raw_metadata: z.record(z.unknown()).default({}),
  }),
  deals: z.array(normalizedDealSchema).max(10000),
  trades: z.array(normalizedTradeSchema).max(5000),
  last_deal_time: z.string().datetime({ offset: true }).nullable(),
  last_deal_ticket: z.string().nullable(),
  sync_status: z.enum(['connected', 'failed', 'terminal_offline']).default('connected'),
  sync_error: z.string().max(2000).nullable().default(null),
});

async function ownedAccount(admin: SupabaseClient, id: string, userId: string) {
  const { data, error } = await admin.from('trading_accounts').select('*').eq('id', id).eq('user_id', userId).maybeSingle();
  if (error) throw error;
  if (!data) throw new HttpError(404, 'Trading account not found.');
  return data as Record<string, unknown>;
}

async function requireConnector(request: Request, admin: SupabaseClient, accountId: string) {
  const digest = await sha256Hex(bearerToken(request));
  const { data, error } = await admin
    .from('trading_accounts')
    .select('id')
    .eq('id', accountId)
    .eq('connector_token_hash', digest)
    .maybeSingle();
  if (error) throw error;
  if (!data) throw new HttpError(401, 'Connector authentication failed.');
}

async function registration(request: Request, admin: SupabaseClient, body: unknown) {
  const input = registrationSchema.parse(body);
  const forwarded = request.headers.get('x-forwarded-for')?.split(',')[0]?.trim();
  const clientIdentity = request.headers.get('cf-connecting-ip') || forwarded || request.headers.get('x-real-ip') || 'unknown';
  const clientHash = await sha256Hex(`${clientIdentity}\0${request.headers.get('user-agent') ?? ''}`);
  const { data: limit, error: limitError } = await admin.rpc('take_registration_attempt', { p_client_hash: clientHash });
  if (limitError) throw limitError;
  const result = limit as { allowed?: boolean; retry_after_seconds?: number } | null;
  if (!result?.allowed) {
    throw new HttpError(429, 'Too many account creation attempts. Please try again later.', result?.retry_after_seconds ?? 900);
  }
  const { error } = await admin.auth.admin.createUser({
    email: input.email,
    password: input.password,
    email_confirm: true,
    user_metadata: { full_name: input.name },
  });
  if (error) {
    const duplicate = error.message.toLowerCase().includes('already') || error.status === 422;
    throw new HttpError(duplicate ? 409 : 400, duplicate
      ? 'An account with this email already exists. Sign in instead.'
      : 'Could not create your account.');
  }
  return { body: { created: true }, status: 201 };
}

async function userAction(request: Request, admin: SupabaseClient, body: Record<string, unknown>) {
  const user = await requireUser(request, admin);
  switch (body.action) {
    case 'list_accounts': {
      const { data, error } = await admin.from('trading_accounts').select('*').eq('user_id', user.id).order('created_at', { ascending: false });
      if (error) throw error;
      const accounts = await Promise.all((data ?? []).map(async (account) => {
        const { count, error: countError } = await admin.from('trades').select('id', { count: 'exact', head: true })
          .eq('user_id', user.id).eq('trading_account_id', account.id).eq('source', 'mt5');
        if (countError) throw countError;
        return publicAccount({ ...account, imported_trade_count: count ?? 0 });
      }));
      return { body: { accounts }, status: 200 };
    }
    case 'create_account': {
      const input = accountInputSchema.parse(body);
      const historyStart = new Date(Date.now() - input.history_days * 86_400_000).toISOString();
      const { action: _action, history_days: _historyDays, password, ...metadata } = input;
      const { data, error } = await admin.from('trading_accounts').upsert({
        ...metadata,
        user_id: user.id,
        platform: 'MT5',
        broker_password_encrypted: await encryptBrokerPassword(password),
        history_start_at: historyStart,
        sync_status: 'disconnected',
        updated_at: new Date().toISOString(),
      }, { onConflict: 'user_id,broker,platform,account_number,server' }).select('*').single();
      if (error) throw error;
      return { body: { account: publicAccount(data) }, status: 201 };
    }
    case 'get_account': {
      const input = accountActionSchema.parse(body);
      return { body: { account: publicAccount(await ownedAccount(admin, input.account_id, user.id)) }, status: 200 };
    }
    case 'disconnect_account': {
      const input = accountActionSchema.parse(body);
      await ownedAccount(admin, input.account_id, user.id);
      const { error } = await admin.from('trading_accounts').update({
        sync_status: 'disconnected', sync_requested_at: null, connector_token_hash: null,
        pairing_code_hash: null, pairing_expires_at: null, paired_at: null, updated_at: new Date().toISOString(),
      }).eq('id', input.account_id).eq('user_id', user.id);
      if (error) throw error;
      return { body: { status: 'disconnected' }, status: 200 };
    }
    case 'pair_connector': {
      const input = accountActionSchema.parse(body);
      await ownedAccount(admin, input.account_id, user.id);
      const pairingCode = randomSecret();
      const expiresAt = new Date(Date.now() + PAIRING_WINDOW_MS).toISOString();
      const { error } = await admin.from('trading_accounts').update({
        pairing_code_hash: await sha256Hex(pairingCode), pairing_expires_at: expiresAt,
        connector_token_hash: null, paired_at: null, sync_status: 'disconnected', sync_error: null,
        updated_at: new Date().toISOString(),
      }).eq('id', input.account_id).eq('user_id', user.id);
      if (error) throw error;
      return { body: { pairing_code: pairingCode, expires_at: expiresAt }, status: 200 };
    }
    case 'request_sync': {
      const input = accountActionSchema.parse(body);
      await ownedAccount(admin, input.account_id, user.id);
      const requestedAt = new Date().toISOString();
      const { error } = await admin.from('trading_accounts').update({
        sync_status: 'syncing', sync_requested_at: requestedAt, sync_error: null, updated_at: requestedAt,
      }).eq('id', input.account_id).eq('user_id', user.id);
      if (error) throw error;
      return { body: { status: 'syncing', requested_at: requestedAt }, status: 202 };
    }
    case 'request_resync': {
      const input = resyncSchema.parse(body);
      await ownedAccount(admin, input.account_id, user.id);
      const now = new Date();
      const { error } = await admin.from('trading_accounts').update({
        history_start_at: new Date(now.getTime() - input.days * 86_400_000).toISOString(),
        last_deal_time: null, last_deal_ticket: null, sync_status: 'syncing',
        sync_requested_at: now.toISOString(), sync_error: null, updated_at: now.toISOString(),
      }).eq('id', input.account_id).eq('user_id', user.id);
      if (error) throw error;
      return { body: { status: 'syncing', history_days: input.days }, status: 202 };
    }
    default:
      throw new HttpError(400, 'Unknown authenticated action.');
  }
}

async function connectorAction(request: Request, admin: SupabaseClient, body: Record<string, unknown>) {
  if (body.action === 'mt5_pair') {
    const input = z.object({ action: z.literal('mt5_pair'), pairing_code: z.string().min(32).max(128) }).strict().parse(body);
    const connectorToken = randomSecret();
    const now = new Date().toISOString();
    const { data: account, error } = await admin.from('trading_accounts').update({
      connector_token_hash: await sha256Hex(connectorToken), pairing_code_hash: null,
      pairing_expires_at: null, paired_at: now, updated_at: now,
    }).eq('pairing_code_hash', await sha256Hex(input.pairing_code)).gt('pairing_expires_at', now)
      .select('id,broker,platform,account_number,server').maybeSingle();
    if (error) throw error;
    if (!account) throw new HttpError(401, 'Pairing code is invalid, expired, or already used.');
    return { body: { trading_account_id: account.id, connector_token: connectorToken, account }, status: 200 };
  }
  if (body.action === 'mt5_get_account') {
    const input = z.object({ action: z.literal('mt5_get_account'), account_id: z.string().uuid() }).strict().parse(body);
    await requireConnector(request, admin, input.account_id);
    const { data, error } = await admin.from('trading_accounts')
      .select('id,broker,platform,account_number,server,history_start_at,last_deal_time,last_deal_ticket,sync_status,sync_requested_at')
      .eq('id', input.account_id).maybeSingle();
    if (error) throw error;
    if (!data) throw new HttpError(404, 'Trading account not found.');
    return { body: { account: data }, status: 200 };
  }
  if (body.action === 'mt5_sync') {
    const input = z.object({ action: z.literal('mt5_sync'), payload: mt5PayloadSchema }).strict().parse(body);
    const payload = input.payload;
    await requireConnector(request, admin, payload.trading_account_id);
    const { data: account, error: accountError } = await admin.from('trading_accounts')
      .select('id,account_number').eq('id', payload.trading_account_id).maybeSingle();
    if (accountError) throw accountError;
    if (!account) throw new HttpError(404, 'Trading account not found.');
    if (payload.account.login && payload.account.login !== account.account_number) {
      throw new HttpError(409, 'Connected MT5 login does not match the registered account.');
    }
    const { data, error } = await admin.rpc('ingest_mt5_sync', {
      p_trading_account_id: payload.trading_account_id, p_account: payload.account,
      p_deals: payload.deals, p_trades: payload.trades, p_last_deal_time: payload.last_deal_time,
      p_last_deal_ticket: payload.last_deal_ticket, p_sync_status: payload.sync_status,
      p_sync_error: payload.sync_error,
    });
    if (error) throw error;
    return { body: { result: data }, status: 200 };
  }
  throw new HttpError(400, 'Unknown connector action.');
}

Deno.serve(async (request) => {
  const origin = request.headers.get('origin');
  if (request.method === 'OPTIONS') {
    if (origin && !ALLOWED_ORIGINS.has(origin)) return json(request, { error: 'Origin is not allowed.' }, 403);
    return new Response('ok', { headers: corsHeaders(request) });
  }
  if (request.method !== 'POST') return json(request, { error: 'Method not allowed.' }, 405, { Allow: 'POST, OPTIONS' });
  if (origin && !ALLOWED_ORIGINS.has(origin)) return json(request, { error: 'Origin is not allowed.' }, 403);

  try {
    const body = await request.json() as Record<string, unknown>;
    if (!body || typeof body !== 'object' || typeof body.action !== 'string') throw new HttpError(400, 'A valid action is required.');
    const admin = adminClient();
    const result = body.action === 'register'
      ? await registration(request, admin, body)
      : body.action.startsWith('mt5_')
        ? await connectorAction(request, admin, body)
        : await userAction(request, admin, body);
    return json(request, result.body, result.status);
  } catch (error) {
    if (error instanceof HttpError) {
      return json(request, { error: error.message }, error.status,
        error.retryAfter ? { 'Retry-After': String(error.retryAfter) } : {});
    }
    if (error instanceof z.ZodError) {
      return json(request, { error: 'The request contains invalid or missing values.' }, 400);
    }
    console.error(JSON.stringify({ event: 'journal_api_error', message: error instanceof Error ? error.message : 'Unknown error' }));
    return json(request, { error: 'The server could not complete this request.' }, 500);
  }
});
