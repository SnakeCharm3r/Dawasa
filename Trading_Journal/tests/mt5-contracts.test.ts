import assert from 'node:assert/strict';
import test from 'node:test';
import { BROKER_TRADE_UPDATE_FIELDS, mt5SyncPayloadSchema } from '../lib/mt5/contracts';

const validPayload = {
  trading_account_id: '00000000-0000-4000-8000-000000000001',
  account: { login: '12345678', raw_metadata: {} },
  deals: [
    {
      broker_deal_id: '10',
      broker_position_id: '100',
      entry_type: 'in',
      deal_type: 'buy',
      symbol: 'XAUUSD',
      side: 'buy',
      deal_time: '2026-08-08T10:00:00+00:00',
      price: 3400,
      volume: 0.1,
      raw_metadata: {},
    },
  ],
  trades: [
    {
      broker_position_id: '100',
      broker_deal_ids: ['10', '11'],
      symbol: 'XAUUSD',
      side: 'buy',
      volume: 0.1,
      entry_price: 3400,
      exit_price: 3410,
      open_time: '2026-08-08T10:00:00+00:00',
      close_time: '2026-08-08T11:00:00+00:00',
      gross_profit: 100,
      net_profit: 96,
      raw_metadata: {},
    },
  ],
  last_deal_time: '2026-08-08T11:00:00+00:00',
  last_deal_ticket: '11',
};

test('accepts a normalized MT5 sync batch', () => {
  const parsed = mt5SyncPayloadSchema.parse(validPayload);
  assert.equal(parsed.trades[0].side, 'buy');
  assert.equal(parsed.sync_status, 'connected');
});

test('rejects malformed broker payloads', () => {
  assert.throws(() => mt5SyncPayloadSchema.parse({ ...validPayload, trading_account_id: 'bad-id' }));
  assert.throws(() => mt5SyncPayloadSchema.parse({ ...validPayload, trades: [{ symbol: 'XAUUSD' }] }));
});

test('broker refresh fields cannot overwrite journal review fields', () => {
  const brokerFields = new Set<string>(BROKER_TRADE_UPDATE_FIELDS);
  for (const protectedField of [
    'strategy',
    'setup_notes',
    'lessons_learned',
    'screenshots',
    'tags',
    'emotions',
    'mistakes',
    'grade',
    'planned_risk',
    'review_comments',
  ]) {
    assert.equal(brokerFields.has(protectedField), false, `${protectedField} must remain journal-owned`);
  }
});
