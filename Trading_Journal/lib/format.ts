export function fmtMoney(value: number | null | undefined, opts: { sign?: boolean; decimals?: number } = {}): string {
  if (value == null || Number.isNaN(value)) return '—';
  const { sign = false, decimals = 2 } = opts;
  const abs = Math.abs(value);
  const prefix = value < 0 ? '-$' : sign ? '+$' : '$';
  return `${prefix}${abs.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })}`;
}

export function fmtNum(value: number | null | undefined, decimals = 2): string {
  if (value == null || Number.isNaN(value)) return '—';
  return value.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
}

export function fmtPct(value: number | null | undefined, decimals = 1): string {
  if (value == null || Number.isNaN(value)) return '—';
  return `${value.toFixed(decimals)}%`;
}

export function fmtPrice(value: number | null | undefined): string {
  if (value == null || Number.isNaN(value)) return '—';
  return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 5 });
}

export function fmtDate(iso: string): string {
  const d = new Date(iso + 'T00:00:00');
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

export function moneyColor(value: number | null | undefined): string {
  if (value == null) return '';
  if (value > 0) return 'text-emerald-600 dark:text-emerald-400';
  if (value < 0) return 'text-red-600 dark:text-red-400';
  return 'text-muted-foreground';
}
