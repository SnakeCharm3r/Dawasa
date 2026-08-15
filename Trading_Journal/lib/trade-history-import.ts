import { read, utils, type WorkSheet } from 'xlsx';
import { parseCsv, parsedRowToTradeInput } from './csv';
import type { Trade } from './types';

export type ImportedTradeInput = Omit<
  Trade,
  'id' | 'created_at' | 'updated_at' | 'user_id'
>;

export type TradeHistoryImport = {
  format: 'MT5 position report' | 'Journal spreadsheet';
  trades: ImportedTradeInput[];
  warnings: string[];
};

const text = (value: unknown) => String(value ?? '').trim();
const header = (value: unknown) => text(value).toLowerCase().replace(/[^a-z0-9]+/g, '');

function numberValue(value: unknown): number | null {
  const cleaned = text(value).replace(/[$,\s]/g, '');
  if (!cleaned) return null;
  const parsed = Number(cleaned);
  return Number.isFinite(parsed) ? parsed : null;
}

function mt5DateTime(value: unknown): string | null {
  const match = text(value).match(
    /^(\d{4})[.\/-](\d{2})[.\/-](\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/
  );
  if (!match) return null;
  const [, year, month, day, hour = '00', minute = '00', second = '00'] = match;
  return `${year}-${month}-${day}T${hour}:${minute}:${second}`;
}

function sheetRows(sheet: WorkSheet): unknown[][] {
  return utils.sheet_to_json<unknown[]>(sheet, {
    header: 1,
    raw: false,
    defval: '',
    blankrows: false,
    dateNF: 'yyyy-mm-dd hh:mm:ss',
  });
}

function positionHeaderIndex(rows: unknown[][]): number {
  return rows.findIndex((row) => {
    const cells = row.map(header);
    return cells.includes('position') && cells.includes('symbol') &&
      cells.includes('type') && cells.includes('volume') &&
      cells.filter((cell) => cell === 'time').length >= 2;
  });
}

// MT5 HTML reports use hidden cells with colspan attributes. Spreadsheet readers
// preserve those empty cells, shifting the visible position values to column 13.
function positionCells(row: unknown[]): unknown[] {
  const hasHtmlColspanGap = row.length >= 21 && row.slice(4, 12).every((cell) => !text(cell));
  return hasHtmlColspanGap ? [...row.slice(0, 4), ...row.slice(12, 21)] : row.slice(0, 13);
}

function parseMt5Positions(rows: unknown[][], startNumber: number): TradeHistoryImport | null {
  const headerIndex = positionHeaderIndex(rows);
  if (headerIndex < 0) return null;

  const trades: ImportedTradeInput[] = [];
  const warnings: string[] = [];
  const seenPositions = new Set<string>();

  for (let index = headerIndex + 1; index < rows.length; index++) {
    const first = header(rows[index][0]);
    if (['orders', 'deals', 'summary'].includes(first)) break;

    const cells = positionCells(rows[index]);
    const [openRaw, positionRaw, symbolRaw, sideRaw, volumeRaw, entryRaw, slRaw,
      tpRaw, closeRaw, exitRaw, commissionRaw, swapRaw, profitRaw] = cells;
    const openTime = mt5DateTime(openRaw);
    const closeTime = mt5DateTime(closeRaw);
    const positionId = text(positionRaw);
    const symbol = text(symbolRaw);
    const side = text(sideRaw).toLowerCase();

    if (!openTime || !closeTime || !positionId || !symbol || !['buy', 'sell'].includes(side)) {
      continue;
    }
    if (seenPositions.has(positionId)) {
      warnings.push(`Position ${positionId} appeared more than once and was included once.`);
      continue;
    }

    const volume = numberValue(volumeRaw);
    const entryPrice = numberValue(entryRaw);
    const exitPrice = numberValue(exitRaw);
    if (volume == null || entryPrice == null || exitPrice == null) {
      warnings.push(`Position ${positionId} was skipped because volume or price was missing.`);
      continue;
    }

    const commission = numberValue(commissionRaw) ?? 0;
    const swap = numberValue(swapRaw) ?? 0;
    const grossProfit = numberValue(profitRaw) ?? 0;
    const netProfit = grossProfit + commission + swap;
    const costs = Math.max(0, -commission) + Math.max(0, -swap);
    seenPositions.add(positionId);
    trades.push({
      trade_number: startNumber + trades.length,
      date: closeTime.slice(0, 10),
      symbol,
      direction: side === 'sell' ? 'Short' : 'Long',
      strategy: 'Unreviewed',
      calc_mode: 'pips',
      entry_price: entryPrice,
      exit_price: exitPrice,
      lot_size: volume,
      fees: costs,
      stop_loss: numberValue(slRaw),
      target_price: numberValue(tpRaw),
      setup_notes: null,
      lessons_learned: null,
      source: 'mt5',
      broker_position_id: positionId,
      broker_deal_ids: [],
      side: side as 'buy' | 'sell',
      volume,
      open_time: openTime,
      close_time: closeTime,
      take_profit: numberValue(tpRaw),
      commission,
      swap,
      fee: 0,
      gross_profit: grossProfit,
      net_profit: netProfit,
      raw_broker_metadata: { import_source: 'mt5_history_file' },
    });
  }

  if (!trades.length) {
    throw new Error('An MT5 Positions table was found, but it contained no closed trades.');
  }
  return { format: 'MT5 position report', trades, warnings };
}

function parseJournalSheet(rows: unknown[][], startNumber: number): TradeHistoryImport | null {
  const headerIndex = rows.findIndex((row) => {
    const cells = row.map(header);
    return cells.includes('symbol') && cells.includes('entryprice') && cells.includes('exitprice');
  });
  if (headerIndex < 0) return null;

  const csv = utils.sheet_to_csv(utils.aoa_to_sheet(rows.slice(headerIndex)));
  const parsed = parseCsv(csv);
  const warnings: string[] = [];
  const trades = parsed.map((row, index) => {
    const converted = parsedRowToTradeInput(row, startNumber + index);
    warnings.push(...converted.warnings.map((warning) => `Row ${index + 2}: ${warning}`));
    return { ...converted.input, source: 'manual' as const };
  });
  return trades.length ? { format: 'Journal spreadsheet', trades, warnings } : null;
}

export function parseTradeHistoryData(
  data: ArrayBuffer | Uint8Array,
  startNumber: number
): TradeHistoryImport {
  const workbook = read(data, { type: 'array', cellDates: true });
  for (const sheetName of workbook.SheetNames) {
    const rows = sheetRows(workbook.Sheets[sheetName]);
    const mt5 = parseMt5Positions(rows, startNumber);
    if (mt5) return mt5;
    const journal = parseJournalSheet(rows, startNumber);
    if (journal) return journal;
  }
  throw new Error(
    'No supported trade table was found. Upload an MT5 Positions report or a journal CSV/XLS/XLSX/ODS file.'
  );
}

export async function parseTradeHistoryFile(
  file: File,
  startNumber: number
): Promise<TradeHistoryImport> {
  return parseTradeHistoryData(await file.arrayBuffer(), startNumber);
}
