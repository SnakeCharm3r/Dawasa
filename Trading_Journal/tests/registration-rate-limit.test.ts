import assert from 'node:assert/strict';
import test from 'node:test';
import { registrationClientKey, takeRegistrationAttempt } from '../lib/server/registration-rate-limit';

test('registration rate limiting allows five attempts per client window', () => {
  const key = `test-client-${Date.now()}`;
  const start = 1_000;

  for (let attempt = 0; attempt < 5; attempt += 1) {
    assert.equal(takeRegistrationAttempt(key, start + attempt).allowed, true);
  }
  assert.equal(takeRegistrationAttempt(key, start + 5).allowed, false);
  assert.equal(takeRegistrationAttempt(key, start + 15 * 60_000).allowed, true);
});

test('registration client key prefers the first forwarded address', () => {
  const headers = new Headers({ 'x-forwarded-for': '203.0.113.10, 10.0.0.2' });
  assert.equal(registrationClientKey(headers), '203.0.113.10');
});
