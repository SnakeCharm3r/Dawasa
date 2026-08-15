import assert from 'node:assert/strict';
import test from 'node:test';
import { hashSecret, tokensMatch } from '../lib/server/internal-auth';
import { readBearerToken, UnauthorizedError } from '../lib/server/auth';
import { publicTradingAccount } from '../lib/server/trading-accounts';
import { decryptBrokerPassword, encryptBrokerPassword } from '../lib/server/broker-credentials';
import type { NextRequest } from 'next/server';

function requestWithAuthorization(value?: string) {
  return { headers: new Headers(value ? { authorization: value } : {}) } as NextRequest;
}

test('internal API token requires an exact match', () => {
  assert.equal(tokensMatch('correct-token', 'correct-token'), true);
  assert.equal(tokensMatch('wrong-token', 'correct-token'), false);
  assert.equal(tokensMatch(null, 'correct-token'), false);
  assert.equal(tokensMatch('correct-token', undefined), false);
});

test('user endpoints require a bearer access token', () => {
  assert.throws(() => readBearerToken(requestWithAuthorization()), UnauthorizedError);
  assert.throws(() => readBearerToken(requestWithAuthorization('Basic abc')), UnauthorizedError);
  assert.equal(readBearerToken(requestWithAuthorization('Bearer user-jwt')), 'user-jwt');
});

test('pairing secrets are hashed and never returned in public account data', () => {
  assert.notEqual(hashSecret('one-time-code'), 'one-time-code');
  assert.equal(hashSecret('one-time-code'), hashSecret('one-time-code'));
  const account = publicTradingAccount({
    account_number: '12345678',
    connector_token_hash: 'connector-digest',
    pairing_code_hash: 'pairing-digest',
    broker_password_encrypted: 'encrypted-password',
  });
  assert.equal(account.account_number, '****5678');
  assert.equal('connector_token_hash' in account, false);
  assert.equal('pairing_code_hash' in account, false);
  assert.equal('broker_password_encrypted' in account, false);
});

test('broker passwords are authenticated-encrypted at rest', () => {
  const previousKey = process.env.BROKER_CREDENTIAL_ENCRYPTION_KEY;
  process.env.BROKER_CREDENTIAL_ENCRYPTION_KEY = 'test-only-credential-key';

  try {
    const encrypted = encryptBrokerPassword('investor-password');
    assert.notEqual(encrypted, 'investor-password');
    assert.equal(decryptBrokerPassword(encrypted), 'investor-password');
    assert.throws(() => decryptBrokerPassword(`${encrypted}tampered`));
  } finally {
    process.env.BROKER_CREDENTIAL_ENCRYPTION_KEY = previousKey;
  }
});
