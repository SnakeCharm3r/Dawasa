import { read, utils, type WorkSheet } from 'xlsx';
import { parseCsv, parsedRowToTradeInput } from './csv';
import type { Trade } from './types';

export type ImportedTradeInput = Omit<
  Trade,
  'id' | 'created_at' | 'updated_at' | 'user_id'
>;

export type TradeHistoryImport = {
  format: 'MT5 position report' | 'cTrader account statement' | 'Journal spreadsheet';
  trades: ImportedTradeInput[];
  warnings: string[];
};

export type PositionedPdfText = {
  page: number;
  x: number;
  y: number;
  text: string;
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

const MONTHS: Record<string, string> = {
  jan: '01', feb: '02', mar: '03', apr: '04', may: '05', jun: '06',
  jul: '07', aug: '08', sep: '09', oct: '10', nov: '11', dec: '12',
};

function cTraderDateTime(value: unknown, utcOffset: string | null) {
  const match = text(value).match(
    /^(\d{1,2})\s+([A-Za-z]{3})\s+(\d{4})\s+(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,3}))?$/
  );
  if (!match) return null;
  const [, day, monthName, year, hour, minute, second, milliseconds = '000'] = match;
  const month = MONTHS[monthName.toLowerCase()];
  if (!month) return null;
  return `${year}-${month}-${day.padStart(2, '0')}T${hour}:${minute}:${second}.${milliseconds.padEnd(3, '0')}${utcOffset ?? ''}`;
}

function compactColumn(items: PositionedPdfText[], minimumX: number, maximumX = Number.POSITIVE_INFINITY) {
  return items
    .filter((item) => item.x >= minimumX && item.x < maximumX)
    .sort((a, b) => a.x - b.x)
    .map((item) => item.text.trim())
    .filter(Boolean)
    .join('');
}

function stableDealId(parts: Array<string | number>) {
  return `ctrader:${parts.map((part) => encodeURIComponent(String(part))).join(':')}`;
}

export function parseCtraderStatementRows(
  positionedText: PositionedPdfText[],
  startNumber: number
): TradeHistoryImport | null {
  const rows = new Map<string, PositionedPdfText[]>();
  for (const item of positionedText) {
    const key = `${item.page}:${Math.round(item.y * 2) / 2}`;
    const row = rows.get(key) ?? [];
    row.push(item);
    rows.set(key, row);
  }
  const orderedRows = Array.from(rows.values())
    .map((row: PositionedPdfText[]) => row.sort((a: PositionedPdfText, b: PositionedPdfText) => a.x - b.x))
    .sort((a, b) => a[0].page - b[0].page || b[0].y - a[0].y);
  const lines = orderedRows.map((row: PositionedPdfText[]) => row.map((item: PositionedPdfText) => item.text).join(' ').replace(/\s+/g, ' ').trim());
  const isCtrader = lines.some((line) =>
    line.includes('Opening Direction') && line.includes('Closing Time') && line.includes('Closing Quantity')
  );
  if (!isCtrader) return null;

  const account = lines.map((line) => line.match(/Account:\s*(\d+)/i)?.[1]).find(Boolean) ?? null;
  const currency = lines.map((line) => line.match(/Currency:\s*([A-Z]{3})/i)?.[1]).find(Boolean) ?? null;
  const broker = lines.find((line) => /Pepperstone/i.test(line))?.match(/Pepperstone/i)?.[0] ?? null;
  const rawOffset = lines.map((line) => line.match(/UTC\s*([+-]\d{1,2})/i)?.[1]).find(Boolean) ?? null;
  const utcOffset = rawOffset
    ? `${rawOffset.startsWith('-') ? '-' : '+'}${rawOffset.replace(/[+-]/, '').padStart(2, '0')}:00`
    : null;
  const offsetLabel = rawOffset ? `UTC${rawOffset}` : null;
  const signatureOccurrences = new Map<string, number>();
  const trades: ImportedTradeInput[] = [];

  for (const row of orderedRows) {
    const symbol = compactColumn(row, 20, 90);
    const sideText = compactColumn(row, 90, 150).toLowerCase();
    const closeText = compactColumn(row, 150, 270);
    const entryPrice = numberValue(compactColumn(row, 270, 325));
    const exitPrice = numberValue(compactColumn(row, 325, 385));
    const volume = numberValue(compactColumn(row, 385, 465).replace(/lots?/i, ''));
    const netProfit = numberValue(compactColumn(row, 465, 505));
    const closingBalance = numberValue(compactColumn(row, 505));
    const closeTime = cTraderDateTime(closeText, utcOffset);
    if (!closeTime || !symbol || !['buy', 'sell'].includes(sideText) ||
        entryPrice == null || exitPrice == null || volume == null || netProfit == null) {
      continue;
    }

    const signature = [account ?? 'unknown', closeTime, symbol, sideText, entryPrice, exitPrice, volume, netProfit].join('|');
    const occurrence = (signatureOccurrences.get(signature) ?? 0) + 1;
    signatureOccurrences.set(signature, occurrence);
    trades.push({
      trade_number: startNumber + trades.length,
      date: closeTime.slice(0, 10),
      symbol,
      direction: sideText === 'sell' ? 'Short' : 'Long',
      strategy: 'Unreviewed',
      calc_mode: 'pips',
      entry_price: entryPrice,
      exit_price: exitPrice,
      lot_size: volume,
      fees: 0,
      stop_loss: null,
      target_price: null,
      setup_notes: null,
      lessons_learned: null,
      source: 'manual',
      broker_position_id: stableDealId([account ?? 'unknown', closeTime, symbol, sideText, entryPrice, exitPrice, volume, netProfit, occurrence]),
      broker_deal_ids: [],
      side: sideText as 'buy' | 'sell',
      volume,
      open_time: null,
      close_time: closeTime,
      take_profit: null,
      commission: 0,
      swap: 0,
      fee: 0,
      gross_profit: netProfit,
      net_profit: netProfit,
      raw_broker_metadata: {
        import_source: 'ctrader_account_statement_pdf',
        platform: 'cTrader',
        broker,
        account,
        account_currency: currency,
        statement_timezone: offsetLabel,
        closing_balance: closingBalance,
        opening_time_available: false,
      },
    });
  }

  if (!trades.length) {
    throw new Error('A cTrader Deals table was found, but it contained no readable closed trades.');
  }
  return {
    format: 'cTrader account statement',
    trades,
    warnings: ['cTrader account statements do not provide opening times; those fields were left empty.'],
  };
}

export async function parseTradeHistoryPdfData(
  data: ArrayBuffer | Uint8Array,
  startNumber: number
): Promise<TradeHistoryImport> {
  const pdfjs = await import('pdfjs-dist/legacy/build/pdf.mjs');
  if (typeof window !== 'undefined') {
    pdfjs.GlobalWorkerOptions.workerSrc = new URL('/pdf.worker.min.mjs', window.location.origin).toString();
  }
  const bytes = data instanceof Uint8Array ? data : new Uint8Array(data);
  const document = await pdfjs.getDocument({ data: bytes }).promise;
  const positionedText: PositionedPdfText[] = [];
  try {
    for (let pageNumber = 1; pageNumber <= document.numPages; pageNumber++) {
      const page = await document.getPage(pageNumber);
      const content = await page.getTextContent();
      for (const item of content.items) {
        if (!('str' in item) || !item.str.trim()) continue;
        positionedText.push({
          page: pageNumber,
          x: item.transform[4],
          y: item.transform[5],
          text: item.str,
        });
      }
      page.cleanup();
    }
  } finally {
    await document.destroy();
  }
  const cTrader = parseCtraderStatementRows(positionedText, startNumber);
  if (cTrader) return cTrader;
  throw new Error('No supported cTrader Deals table was found in this PDF.');
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
  const data = await file.arrayBuffer();
  const signature = new TextDecoder().decode(new Uint8Array(data).slice(0, 5));
  if (signature === '%PDF-') return parseTradeHistoryPdfData(data, startNumber);
  return parseTradeHistoryData(data, startNumber);
}
