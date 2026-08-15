import assert from 'node:assert/strict';
import test from 'node:test';
import { idleDeadline, isSessionIdle } from '../hooks/use-idle-session';

test('calculates idle-session expiry from the last activity time', () => {
  assert.equal(idleDeadline(1_000, 30), 1_801_000);
  assert.equal(isSessionIdle(1_000, 30, 1_800_999), false);
  assert.equal(isSessionIdle(1_000, 30, 1_801_000), true);
});

test('zero disables automatic idle expiry', () => {
  assert.equal(isSessionIdle(1_000, 0, Number.MAX_SAFE_INTEGER), false);
});
