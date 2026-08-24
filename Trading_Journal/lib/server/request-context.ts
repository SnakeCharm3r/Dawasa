import { isIP } from 'node:net';
import type { NextRequest } from 'next/server';

export type NetworkKind = 'public' | 'local' | 'internal' | 'unknown';

function cleanIp(value: string) {
  let ip = value.trim().replace(/^for=/i, '').replace(/^"|"$/g, '');
  if (ip.startsWith('[')) ip = ip.slice(1, ip.indexOf(']'));
  if (ip.startsWith('::ffff:')) ip = ip.slice(7);
  if (isIP(ip)) return ip;
  const ipv4WithPort = ip.match(/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/);
  return ipv4WithPort && isIP(ipv4WithPort[1]) ? ipv4WithPort[1] : null;
}

export function classifyIp(input: string): NetworkKind {
  const ip = cleanIp(input);
  if (!ip) return 'unknown';
  if (ip === '127.0.0.1' || ip === '::1') return 'local';
  if (isIP(ip) === 6) {
    const value = ip.toLowerCase();
    if (value === '::' || value.startsWith('fc') || value.startsWith('fd') || value.startsWith('fe8') || value.startsWith('fe9') || value.startsWith('fea') || value.startsWith('feb')) return 'internal';
    return 'public';
  }

  const parts = ip.split('.').map(Number);
  const [a, b] = parts;
  if (a === 10 || (a === 172 && b >= 16 && b <= 31) || (a === 192 && b === 168)) return 'internal';
  if (
    a === 0 || a === 127 || a >= 224 ||
    (a === 100 && b >= 64 && b <= 127) ||
    (a === 169 && b === 254) ||
    (a === 192 && b === 0 && parts[2] === 2) ||
    (a === 198 && b === 51 && parts[2] === 100) ||
    (a === 203 && b === 0 && parts[2] === 113)
  ) return 'internal';
  return 'public';
}

export function extractRequestIp(request: Pick<NextRequest, 'headers'> & { ip?: string }) {
  const trustProxy = process.env.TRUST_PROXY_HEADERS === 'true' || process.env.NODE_ENV !== 'production';
  const candidates: string[] = [];
  if (trustProxy) {
    const cloudflare = request.headers.get('cf-connecting-ip');
    if (cloudflare) candidates.push(cloudflare);
    const forwarded = request.headers.get('x-forwarded-for');
    if (forwarded) candidates.push(...forwarded.split(','));
    const real = request.headers.get('x-real-ip');
    if (real) candidates.push(real);
  }
  if (request.ip) candidates.push(request.ip);

  const valid = candidates.map(cleanIp).filter((ip): ip is string => Boolean(ip));
  return valid.find((ip) => classifyIp(ip) === 'public') ?? valid[0] ?? null;
}

export function parseUserAgent(userAgent: string | null) {
  const ua = userAgent ?? '';
  let browser = 'Unknown';
  if (/Edg\//i.test(ua)) browser = 'Microsoft Edge';
  else if (/OPR\//i.test(ua)) browser = 'Opera';
  else if (/SamsungBrowser/i.test(ua)) browser = 'Samsung Internet';
  else if (/Chrome\//i.test(ua) && !/Chromium/i.test(ua)) browser = 'Chrome';
  else if (/Firefox\//i.test(ua)) browser = 'Firefox';
  else if (/Safari\//i.test(ua) && /Mobile\//i.test(ua)) browser = 'Mobile Safari';
  else if (/Safari\//i.test(ua)) browser = 'Safari';

  let operatingSystem = 'Unknown';
  if (/Windows NT/i.test(ua)) operatingSystem = 'Windows';
  else if (/Android/i.test(ua)) operatingSystem = 'Android';
  else if (/iPhone|iPad|iPod/i.test(ua)) operatingSystem = 'iOS';
  else if (/Mac OS X/i.test(ua)) operatingSystem = 'macOS';
  else if (/Linux/i.test(ua)) operatingSystem = 'Linux';

  let deviceType = 'Desktop';
  if (/bot|crawler|spider|slurp/i.test(ua)) deviceType = 'Bot';
  else if (/iPad|Tablet/i.test(ua)) deviceType = 'Tablet';
  else if (/Mobile|iPhone|Android/i.test(ua)) deviceType = 'Mobile';
  else if (!ua) deviceType = 'Unknown';

  return { browser, operatingSystem, deviceType };
}

export function requestDeviceContext(request: Pick<NextRequest, 'headers'> & { ip?: string }) {
  const userAgent = request.headers.get('user-agent');
  return {
    ipAddress: extractRequestIp(request),
    userAgent,
    ...parseUserAgent(userAgent),
  };
}
