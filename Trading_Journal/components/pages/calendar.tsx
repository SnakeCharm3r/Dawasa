'use client';

import { useMemo, useState } from 'react';
import {
  ChevronLeft,
  ChevronRight,
  CalendarRange,
  X,
  TrendingUp,
  TrendingDown,
  Minus,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { fmtMoney, fmtDate, moneyColor } from '@/lib/format';
import { buildMonthDates } from '@/lib/calendar';
import { cn } from '@/lib/utils';
import type { TradeWithCalc } from '@/lib/types';
import type { useJournalData } from '@/hooks/use-journal-data';

type Journal = ReturnType<typeof useJournalData>;

const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const WEEKDAYS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

interface DayCell {
  date: Date;
  dateKey: string;
  inMonth: boolean;
  trades: TradeWithCalc[];
  netPnl: number;
  count: number;
}

export function CalendarPage({ journal }: { journal: Journal }) {
  const { tradesWithCalc, settings } = journal;
  const today = useMemo(() => new Date(), []);
  const [year, setYear] = useState(today.getFullYear());
  const [month, setMonth] = useState(today.getMonth());
  const [selectedDay, setSelectedDay] = useState<DayCell | null>(null);

  const tradesByDate = useMemo(() => {
    const map = new Map<string, TradeWithCalc[]>();
    for (const t of tradesWithCalc) {
      const key = t.date;
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(t);
    }
    return map;
  }, [tradesWithCalc]);

  const cells = useMemo(() => buildMonthGrid(year, month, tradesByDate), [year, month, tradesByDate]);

  const monthSummary = useMemo(() => {
    const inMonth = cells.filter((c) => c.inMonth && c.count > 0);
    const netPnl = inMonth.reduce((s, c) => s + c.netPnl, 0);
    const count = inMonth.reduce((s, c) => s + c.count, 0);
    const wins = inMonth.flatMap((c) => c.trades).filter((t) => t.calc.winLoss === 'Win').length;
    const winRate = count > 0 ? (wins / count) * 100 : 0;
    return { netPnl: Math.round(netPnl * 100) / 100, count, winRate: Math.round(winRate * 10) / 10, days: inMonth.length };
  }, [cells]);

  const goPrev = () => {
    if (month === 0) { setMonth(11); setYear((y) => y - 1); }
    else setMonth((m) => m - 1);
  };
  const goNext = () => {
    if (month === 11) { setMonth(0); setYear((y) => y + 1); }
    else setMonth((m) => m + 1);
  };

  const years = useMemo(() => {
    const ys = new Set<number>();
    tradesWithCalc.forEach((t) => ys.add(new Date(t.date + 'T00:00:00').getFullYear()));
    ys.add(today.getFullYear());
    return Array.from(ys).sort((a, b) => a - b);
  }, [tradesWithCalc, today]);

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Calendar</h1>
          <p className="text-sm text-muted-foreground">Daily P&amp;L heatmap — click a day for trade details.</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="icon" onClick={goPrev} aria-label="Previous month"><ChevronLeft className="h-4 w-4" /></Button>
          <div className="flex items-center gap-2 text-sm font-semibold min-w-[160px] justify-center">
            <span>{MONTHS[month]}</span>
            <select
              value={year}
              onChange={(e) => setYear(Number(e.target.value))}
              className="rounded-md border border-input bg-background px-2 py-1 text-sm"
            >
              {years.map((y) => <option key={y} value={y}>{y}</option>)}
            </select>
          </div>
          <Button variant="outline" size="icon" onClick={goNext} aria-label="Next month"><ChevronRight className="h-4 w-4" /></Button>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-4">
        <SummaryStat label="Month Net P&L" value={fmtMoney(monthSummary.netPnl, { sign: true })} accent={moneyColor(monthSummary.netPnl)} />
        <SummaryStat label="Trades" value={String(monthSummary.count)} />
        <SummaryStat label="Win Rate" value={monthSummary.count ? `${monthSummary.winRate}%` : '—'} accent="text-emerald-500" />
        <SummaryStat label="Active Days" value={String(monthSummary.days)} />
      </div>

      <Card className="p-3 sm:p-5">
        <div className="grid grid-cols-7 gap-1 sm:gap-2">
          {WEEKDAYS.map((d) => (
            <div key={d} className="pb-2 text-center text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{d}</div>
          ))}
          {cells.map((cell, i) => (
            <DayCellView key={i} cell={cell} onClick={() => cell.count > 0 && setSelectedDay(cell)} />
          ))}
        </div>
      </Card>

      <Dialog open={!!selectedDay} onOpenChange={(o) => !o && setSelectedDay(null)}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <CalendarRange className="h-4 w-4 text-muted-foreground" />
              {selectedDay ? fmtDate(selectedDay.dateKey) : ''}
            </DialogTitle>
            <DialogDescription>
              {selectedDay ? `${selectedDay.count} trade${selectedDay.count > 1 ? 's' : ''} · ${fmtMoney(selectedDay.netPnl, { sign: true })}` : ''}
            </DialogDescription>
          </DialogHeader>
          {selectedDay && (
            <div className="space-y-2">
              {selectedDay.trades.map((t) => (
                <div key={t.id} className="flex items-center justify-between rounded-md border border-border bg-muted/30 px-3 py-2.5">
                  <div className="min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-medium">{t.symbol}</span>
                      <span className={cn('text-xs', t.direction === 'Long' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400')}>{t.direction}</span>
                      <Badge variant="secondary" className="text-[10px]">{t.strategy}</Badge>
                    </div>
                    <div className="mt-0.5 text-xs text-muted-foreground">
                      #{t.trade_number} · {t.calc.winLoss}
                      {t.calc.riskFlagged && <span className="ml-1 text-red-600 dark:text-red-400">· High Risk</span>}
                    </div>
                  </div>
                  <div className="text-right">
                    <div className={cn('font-semibold tabular-nums', moneyColor(t.calc.netPnl))}>{fmtMoney(t.calc.netPnl, { sign: true })}</div>
                    <div className="text-xs text-muted-foreground tabular-nums">{t.calc.rMultiple != null ? `${t.calc.rMultiple >= 0 ? '+' : ''}${t.calc.rMultiple}R` : ''}</div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}

function DayCellView({ cell, onClick }: { cell: DayCell; onClick: () => void }) {
  const isGreen = cell.count > 0 && cell.netPnl > 0;
  const isRed = cell.count > 0 && cell.netPnl < 0;
  const isNeutral = cell.count > 0 && cell.netPnl === 0;

  const bg = !cell.inMonth
    ? 'bg-transparent'
    : isGreen
    ? 'bg-emerald-400/25 dark:bg-emerald-500/20'
    : isRed
    ? 'bg-red-400/25 dark:bg-red-500/20'
    : isNeutral
    ? 'bg-muted/40'
    : 'bg-transparent';

  const textBase = !cell.inMonth ? 'text-muted-foreground/40' : 'text-muted-foreground';
  const clickable = cell.count > 0;

  return (
    <button
      onClick={onClick}
      disabled={!clickable}
      className={cn(
        'relative flex min-h-[72px] flex-col rounded-md border border-border/40 p-1.5 text-left transition-all sm:min-h-[96px] sm:p-2',
        bg,
        clickable && 'cursor-pointer hover:ring-2 hover:ring-ring/40',
        !cell.inMonth && 'border-transparent'
      )}
    >
      <span className={cn('text-[10px] font-medium sm:text-xs', textBase)}>{cell.date.getDate()}</span>
      {cell.inMonth && cell.count > 0 && (
        <div className="mt-auto flex flex-col items-center justify-center gap-0.5 text-center">
          <span className={cn('text-[10px] font-semibold tabular-nums sm:text-xs', isGreen ? 'text-emerald-700 dark:text-emerald-300' : isRed ? 'text-red-700 dark:text-red-300' : 'text-muted-foreground')}>
            {fmtMoney(cell.netPnl, { sign: true, decimals: 0 })}
          </span>
          <span className="text-[9px] text-muted-foreground sm:text-[10px]">{cell.count} trade{cell.count > 1 ? 's' : ''}</span>
        </div>
      )}
      {cell.inMonth && cell.count === 0 && (
        <div className="mt-auto flex flex-col items-center justify-center text-center">
          <span className="text-[9px] text-muted-foreground/50 sm:text-[10px]">No trades</span>
        </div>
      )}
    </button>
  );
}

function SummaryStat({ label, value, accent = 'text-foreground' }: { label: string; value: string; accent?: string }) {
  return (
    <Card className="p-3">
      <div className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{label}</div>
      <div className={cn('mt-1 text-base font-semibold tabular-nums', accent)}>{value}</div>
    </Card>
  );
}

function buildMonthGrid(year: number, month: number, tradesByDate: Map<string, TradeWithCalc[]>): DayCell[] {
  return buildMonthDates(year, month).map(({ date, dateKey, inMonth }) => {
    const dayTrades = tradesByDate.get(dateKey) ?? [];
    const netPnl = dayTrades.reduce((s, t) => s + t.calc.netPnl, 0);

    return {
      date,
      dateKey,
      inMonth,
      trades: dayTrades,
      netPnl: Math.round(netPnl * 100) / 100,
      count: dayTrades.length,
    };
  });
}
