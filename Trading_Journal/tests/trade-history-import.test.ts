import assert from 'node:assert/strict';
import test from 'node:test';
import { utils, write } from 'xlsx';
import { calcTrade } from '../lib/calc';
import { parseCtraderStatementRows, parseTradeHistoryData, type PositionedPdfText } from '../lib/trade-history-import';
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

function pdfRow(page: number, y: number, cells: Array<[number, string]>): PositionedPdfText[] {
  return cells.map(([x, text]) => ({ page, x, y, text }));
}

test('imports cTrader PDF deals into the existing trade shape', () => {
  const rows = [
    ...pdfRow(1, 700, [[30, 'Account:'], [83, '5286933'], [470, 'Pepperstone'], [520, 'UTC+3']]),
    ...pdfRow(1, 680, [[30, 'Currency:'], [83, 'EUR']]),
    ...pdfRow(1, 565, [[33, 'Symbol'], [74, 'Opening Direction'], [164, 'Closing Time (UTC+3)'], [271, 'Entry price'], [325, 'Closing price'], [387, 'Closing Quantity']]),
    ...pdfRow(1, 546, [[31, 'XAUUSD'], [106, 'Sell'], [159, '26 Jun 2026 10:38:42.655'], [279, '4038.02'], [337, '4034.86'], [412, '1'], [420, 'Lots'], [471, '277.55'], [524, '10'], [537, '102.39']]),
  ];
  const result = parseCtraderStatementRows(rows, 20);
  assert.ok(result);
  assert.equal(result.format, 'cTrader account statement');
  assert.equal(result.trades.length, 1);
  assert.equal(result.trades[0].trade_number, 20);
  assert.equal(result.trades[0].date, '2026-06-26');
  assert.equal(result.trades[0].direction, 'Short');
  assert.equal(result.trades[0].entry_price, 4038.02);
  assert.equal(result.trades[0].exit_price, 4034.86);
  assert.equal(result.trades[0].lot_size, 1);
  assert.equal(result.trades[0].net_profit, 277.55);
  assert.equal(result.trades[0].close_time, '2026-06-26T10:38:42.655+03:00');
  assert.equal(result.trades[0].open_time, null);
  assert.equal(result.trades[0].source, 'manual');
  assert.equal(result.trades[0].raw_broker_metadata?.platform, 'cTrader');
  assert.equal(result.trades[0].raw_broker_metadata?.closing_balance, 10102.39);
});
