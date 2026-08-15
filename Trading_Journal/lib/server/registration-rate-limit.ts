const WINDOW_MS = 15 * 60_000;
const MAX_ATTEMPTS = 5;

type Bucket = { count: number; resetsAt: number };

const buckets = new Map<string, Bucket>();

export function takeRegistrationAttempt(key: string, now = Date.now()) {
  const current = buckets.get(key);
  if (!current || current.resetsAt <= now) {
    const bucket = { count: 1, resetsAt: now + WINDOW_MS };
    buckets.set(key, bucket);
    return { allowed: true, remaining: MAX_ATTEMPTS - 1, resetsAt: bucket.resetsAt };
  }

  if (current.count >= MAX_ATTEMPTS) {
    return { allowed: false, remaining: 0, resetsAt: current.resetsAt };
  }

  current.count += 1;
  return { allowed: true, remaining: MAX_ATTEMPTS - current.count, resetsAt: current.resetsAt };
}

export function registrationClientKey(headers: Headers): string {
  const forwarded = headers.get('x-forwarded-for')?.split(',')[0]?.trim();
  return forwarded || headers.get('x-real-ip')?.trim() || 'unknown-client';
}
