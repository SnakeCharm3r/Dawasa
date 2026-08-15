import assert from 'node:assert/strict';
import test from 'node:test';
import { utils, write } from 'xlsx';
import { calcTrade } from '../lib/calc';
import { parseTradeHistoryData } from '../lib/trade-history-import';
import type { Settings, Trade } from '../lib/types';

function workbookBytes(rows: unknown[][]) {
  const workbook = utils.book_new();
  utils.book_append_sheet(workbook, utils.aoa_to_sheet(rows), 'Trades');
  return write(workbook, { type: 'buffer', bookType: 'xlsx' });
}

test('imports the shifted-column layout produced by MT5 HTML reports', () => {
  const row = Array(21).fill('');
  row[0] = '2026.06.24 07:14:59';
  row[1] = '2883548552';
  row[2] = 'XAUUSDm';
  row[3] = 'sell';
  row[12] = '0.05';
  row[13] = '4086.342';
  row[14] = '4061.935';
  row[15] = '3993.219';
  row[16] = '2026.06.24 11:52:21';
  row[17] = '4054.529';
  row[18] = '-1.25';
  row[19] = '-0.50';
  row[20] = '159.06';

  const result = parseTradeHistoryData(workbookBytes([
    ['Trade History Report'],
    ['Positions'],
    ['Time', 'Position', 'Symbol', 'Type', 'Volume', 'Price', 'S / L', 'T / P',
      'Time', 'Price', 'Commission', 'Swap', 'Profit'],
    row,
    ['Orders'],
  ]), 12);

  assert.equal(result.format, 'MT5 position report');
  assert.equal(result.trades.length, 1);
  assert.deepEqual(result.trades[0], {
    trade_number: 12,
    date: '2026-06-24',
    symbol: 'XAUUSDm',
    direction: 'Short',
    strategy: 'Unreviewed',
    calc_mode: 'pips',
    entry_price: 4086.342,
    exit_price: 4054.529,
    lot_size: 0.05,
    fees: 1.75,
    stop_loss: 4061.935,
    target_price: 3993.219,
    setup_notes: null,
    lessons_learned: null,
    source: 'mt5',
    broker_position_id: '2883548552',
    broker_deal_ids: [],
    side: 'sell',
    volume: 0.05,
    open_time: '2026-06-24T07:14:59',
    close_time: '2026-06-24T11:52:21',
    take_profit: 3993.219,
    commission: -1.25,
    swap: -0.5,
    fee: 0,
    gross_profit: 159.06,
    net_profit: 157.31,
    raw_broker_metadata: { import_source: 'mt5_history_file' },
  });
});

test('broker-imported trades use authoritative MT5 profit in journal calculations', () => {
  const settings: Settings = {
    id: 'settings',
    starting_balance: 10000,
    pip_size: 0.01,
    pip_value_per_lot: 1,
    risk_warning_threshold: 2,
    idle_timeout_minutes: 30,
    updated_at: '',
  };
  const trade = {
    id: 'trade',
    created_at: '',
    updated_at: '',
    trade_number: 1,
    date: '2026-06-24',
    symbol: 'XAUUSDm',
    direction: 'Short',
    strategy: 'Unreviewed',
    calc_mode: 'pips',
    entry_price: 4086.342,
    exit_price: 4054.529,
    lot_size: 0.05,
    fees: 1.75,
    stop_loss: null,
    target_price: null,
    setup_notes: null,
    lessons_learned: null,
    source: 'mt5',
    gross_profit: 159.06,
    net_profit: 157.31,
  } satisfies Trade;

  const calculated = calcTrade(trade, 10000, settings);
  assert.equal(calculated.grossPnl, 159.06);
  assert.equal(calculated.netPnl, 157.31);
  assert.equal(calculated.accountBalance, 10157.31);
});
