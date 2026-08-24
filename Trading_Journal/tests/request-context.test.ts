import assert from 'node:assert/strict';
import test from 'node:test';
import type { NextRequest } from 'next/server';
import { classifyIp, extractRequestIp, parseUserAgent } from '../lib/server/request-context';

function request(headers: Record<string, string>, ip?: string) {
  return { headers: new Headers(headers), ip } as NextRequest & { ip?: string };
}

test('extracts the first valid public IP from trusted proxy headers', () => {
  const previous = process.env.TRUST_PROXY_HEADERS;
  process.env.TRUST_PROXY_HEADERS = 'true';
  try {
    assert.equal(
      extractRequestIp(request({ 'x-forwarded-for': '10.0.0.5, 8.8.8.8', 'x-real-ip': '192.168.1.2' })),
      '8.8.8.8'
    );
  } finally {
    process.env.TRUST_PROXY_HEADERS = previous;
  }
});

test('ignores forwarded headers in production unless the proxy is trusted', () => {
  const previousTrust = process.env.TRUST_PROXY_HEADERS;
  const previousNodeEnv = process.env.NODE_ENV;
  const mutableEnv = process.env as Record<string, string | undefined>;
  process.env.TRUST_PROXY_HEADERS = 'false';
  mutableEnv.NODE_ENV = 'production';
  try {
    assert.equal(extractRequestIp(request({ 'x-forwarded-for': '8.8.8.8' }, '127.0.0.1')), '127.0.0.1');
  } finally {
    process.env.TRUST_PROXY_HEADERS = previousTrust;
    if (previousNodeEnv === undefined) delete mutableEnv.NODE_ENV;
    else mutableEnv.NODE_ENV = previousNodeEnv;
  }
});

test('classifies localhost and private office ranges without external geolocation', () => {
  assert.equal(classifyIp('127.0.0.1'), 'local');
  assert.equal(classifyIp('::1'), 'local');
  assert.equal(classifyIp('10.4.5.6'), 'internal');
  assert.equal(classifyIp('172.16.0.1'), 'internal');
  assert.equal(classifyIp('172.31.255.254'), 'internal');
  assert.equal(classifyIp('192.168.20.2'), 'internal');
  assert.equal(classifyIp('8.8.8.8'), 'public');
});

test('derives browser, operating system, and device type from user-agent', () => {
  const parsed = parseUserAgent(
    'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/150.0.0.0 Mobile Safari/537.36'
  );
  assert.deepEqual(parsed, { browser: 'Chrome', operatingSystem: 'Android', deviceType: 'Mobile' });
});
