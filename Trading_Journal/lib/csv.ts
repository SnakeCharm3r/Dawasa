import type { Settings, Trade, TradeWithCalc } from './types';

const TRADE_FIELDS: (keyof Trade)[] = [
  'trade_number',
  'date',
  'symbol',
  'direction',
  'strategy',
  'calc_mode',
  'entry_price',
  'exit_price',
  'lot_size',
  'fees',
  'stop_loss',
  'target_price',
  'setup_notes',
  'exit_reason',
  'emotion_during_trade',
  'lessons_learned',
];

function escapeCsv(value: unknown): string {
  if (value == null) return '';
  const s = String(value);
  if (s.includes(',') || s.includes('"') || s.includes('\n')) {
    return `"${s.replace(/"/g, '""')}"`;
  }
  return s;
}

export function tradesToCsv(trades: Trade[]): string {
  const header = [...TRADE_FIELDS].join(',');
  const rows = trades.map((t) =>
    TRADE_FIELDS.map((f) => escapeCsv((t as unknown as Record<string, unknown>)[f])).join(',')
  );
  return [header, ...rows].join('\n');
}

export function tradesWithCalcToCsv(trades: TradeWithCalc[]): string {
  const calcFields = [
    'riskPips',
    'riskDollars',
    'riskPercent',
    'rewardPips',
    'rewardDollars',
    'pipsGainedLost',
    'grossPnl',
    'netPnl',
    'rMultiple',
    'pnlPercent',
    'accountBalance',
    'winLoss',
  ] as const;
  const header = [...TRADE_FIELDS, ...calcFields].join(',');
  const rows = trades.map((t) => {
    const base = TRADE_FIELDS.map((f) => escapeCsv((t as unknown as Record<string, unknown>)[f]));
    const calc = calcFields.map((f) => escapeCsv((t.calc as unknown as Record<string, unknown>)[f]));
    return [...base, ...calc].join(',');
  });
  return [header, ...rows].join('\n');
}

export function downloadCsv(filename: string, csv: string): void {
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

type ParsedRow = Partial<Record<keyof Trade, unknown>>;

export function parseCsv(text: string): ParsedRow[] {
  const rows: string[][] = [];
  let current: string[] = [];
  let field = '';
  let inQuotes = false;
  for (let i = 0; i < text.length; i++) {
    const ch = text[i];
    if (inQuotes) {
      if (ch === '"') {
        if (text[i + 1] === '"') {
          field += '"';
          i++;
        } else {
          inQuotes = false;
        }
      } else {
        field += ch;
      }
    } else {
      if (ch === '"') {
        inQuotes = true;
      } else if (ch === ',') {
        current.push(field);
        field = '';
      } else if (ch === '\n' || ch === '\r') {
        if (ch === '\r' && text[i + 1] === '\n') i++;
        current.push(field);
        rows.push(current);
        current = [];
        field = '';
      } else {
        field += ch;
      }
    }
  }
  if (field.length > 0 || current.length > 0) {
    current.push(field);
    rows.push(current);
  }

  if (rows.length === 0) return [];
  const headers = rows[0].map((h) => h.trim()) as (keyof Trade)[];
  const out: ParsedRow[] = [];
  for (let r = 1; r < rows.length; r++) {
    const cells = rows[r];
    if (cells.length === 1 && cells[0].trim() === '') continue;
    const obj: ParsedRow = {};
    for (let c = 0; c < headers.length; c++) {
      obj[headers[c]] = cells[c];
    }
    out.push(obj);
  }
  return out;
}

export function parsedRowToTradeInput(
  row: ParsedRow,
  fallbackNumber: number
): {
  input: {
    trade_number: number;
    date: string;
    symbol: string;
    direction: 'Long' | 'Short';
    strategy: string;
    calc_mode: 'pips' | 'shares';
    entry_price: number;
    exit_price: number;
    lot_size: number;
    fees: number;
    stop_loss: number | null;
    target_price: number | null;
    setup_notes: string | null;
    exit_reason: string | null;
    emotion_during_trade: string | null;
    lessons_learned: string | null;
  };
  warnings: string[];
} {
  const warnings: string[] = [];
  const num = (v: unknown): number | null => {
    if (v == null || v === '') return null;
    const n = Number(String(v).replace(/[$,]/g, ''));
    return Number.isFinite(n) ? n : null;
  };

  const dir = String(row.direction ?? '').trim();
  const direction = dir.toLowerCase().startsWith('s') ? 'Short' : 'Long';
  if (!dir) warnings.push('Missing direction, defaulted to Long.');

  const mode = String(row.calc_mode ?? 'pips').trim().toLowerCase();
  const calc_mode = mode.startsWith('s') ? 'shares' : 'pips';

  const symbol = String(row.symbol ?? '').trim();
  const strategy = String(row.strategy ?? 'Other').trim() || 'Other';
  const date = String(row.date ?? '').trim();
  if (!symbol) warnings.push('Row missing symbol.');
  if (!date) warnings.push('Row missing date.');

  const entry_price = num(row.entry_price);
  const exit_price = num(row.exit_price);
  const lot_size = num(row.lot_size);
  if (entry_price == null || exit_price == null || lot_size == null) {
    warnings.push('Missing entry/exit/lot — row invalid.');
  }

  return {
    input: {
      trade_number: num(row.trade_number) ?? fallbackNumber,
      date: date || new Date().toISOString().slice(0, 10),
      symbol: symbol || 'UNKNOWN',
      direction,
      strategy,
      calc_mode,
      entry_price: entry_price ?? 0,
      exit_price: exit_price ?? 0,
      lot_size: lot_size ?? 0,
      fees: num(row.fees) ?? 0,
      stop_loss: num(row.stop_loss),
      target_price: num(row.target_price),
      setup_notes: String(row.setup_notes ?? '').trim() || null,
      exit_reason: String(row.exit_reason ?? '').trim() || null,
      emotion_during_trade: String(row.emotion_during_trade ?? '').trim() || null,
      lessons_learned: String(row.lessons_learned ?? '').trim() || null,
    },
    warnings,
  };
}

export function exportFullJournalPdfNotSupported(): void {
  // PDF export intentionally omitted from v1; CSV export covers records.
}
