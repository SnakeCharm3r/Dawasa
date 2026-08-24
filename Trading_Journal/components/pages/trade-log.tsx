'use client';

import { useEffect, useMemo, useRef, useState } from 'react';
import {
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  Plus,
  Search,
  Upload,
  Download,
  Pencil,
  Trash2,
  AlertTriangle,
  FileText,
  FileSpreadsheet,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Switch } from '@/components/ui/switch';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { TradeForm, type TradeFormValues } from '@/components/trade-form';
import { tradesWithCalcToCsv, downloadCsv } from '@/lib/csv';
import { fmtMoney, fmtNum, fmtPct, fmtPrice, fmtDate, moneyColor } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Trade, TradeWithCalc, WinLoss } from '@/lib/types';
import type { useJournalData } from '@/hooks/use-journal-data';

type Journal = ReturnType<typeof useJournalData>;

type SortKey = 'date' | 'trade_number' | 'symbol' | 'netPnl' | 'rMultiple' | 'pnlPercent' | 'accountBalance';

const COLUMNS: { key: SortKey | 'static'; label: string; className?: string }[] = [
  { key: 'trade_number', label: '#' },
  { key: 'date', label: 'Date' },
  { key: 'static', label: 'Symbol' },
  { key: 'static', label: 'Dir' },
  { key: 'static', label: 'Strategy' },
  { key: 'static', label: 'Entry' },
  { key: 'static', label: 'Exit' },
  { key: 'static', label: 'Risk $' },
  { key: 'static', label: 'Risk %' },
  { key: 'static', label: 'Pips' },
  { key: 'static', label: 'Gross' },
  { key: 'netPnl', label: 'Net P&L' },
  { key: 'rMultiple', label: 'R' },
  { key: 'pnlPercent', label: 'P&L %' },
  { key: 'accountBalance', label: 'Balance' },
  { key: 'static', label: 'W/L' },
];

export function TradeLogPage({ journal }: { journal: Journal }) {
  const { tradesWithCalc, strategies, trades, addTrade, updateTrade, deleteTrade, deleteTrades, settings, profile } = journal;

  const [search, setSearch] = useState('');
  const [filterSymbol, setFilterSymbol] = useState('all');
  const [filterStrategy, setFilterStrategy] = useState('all');
  const [filterDirection, setFilterDirection] = useState('all');
  const [filterWinLoss, setFilterWinLoss] = useState('all');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [sortKey, setSortKey] = useState<SortKey>('date');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');
  const [editMode, setEditMode] = useState(false);

  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<Trade | null>(null);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(() => new Set());
  const [bulkDeleteOpen, setBulkDeleteOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [importMsg, setImportMsg] = useState<string | null>(null);
  const [importing, setImporting] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);

  const symbols = useMemo(() => Array.from(new Set(trades.map((t) => t.symbol))).sort(), [trades]);

  const filtered = useMemo(() => {
    let list = tradesWithCalc;
    if (search.trim()) {
      const q = search.toLowerCase();
      list = list.filter(
        (t) =>
          (t.setup_notes ?? '').toLowerCase().includes(q) ||
          (t.lessons_learned ?? '').toLowerCase().includes(q) ||
          (t.exit_reason ?? '').toLowerCase().includes(q) ||
          (t.emotion_during_trade ?? '').toLowerCase().includes(q) ||
          (t.emotions ?? []).some((emotion) => emotion.toLowerCase().includes(q)) ||
          t.symbol.toLowerCase().includes(q)
      );
    }
    if (filterSymbol !== 'all') list = list.filter((t) => t.symbol === filterSymbol);
    if (filterStrategy !== 'all') list = list.filter((t) => t.strategy === filterStrategy);
    if (filterDirection !== 'all') list = list.filter((t) => t.direction === filterDirection);
    if (filterWinLoss !== 'all') list = list.filter((t) => t.calc.winLoss === filterWinLoss);
    if (dateFrom) list = list.filter((t) => t.date >= dateFrom);
    if (dateTo) list = list.filter((t) => t.date <= dateTo);

    const sorted = [...list].sort((a, b) => {
      let av: number | string;
      let bv: number | string;
      switch (sortKey) {
        case 'date': av = a.date; bv = b.date; break;
        case 'trade_number': av = a.trade_number; bv = b.trade_number; break;
        case 'symbol': av = a.symbol; bv = b.symbol; break;
        case 'netPnl': av = a.calc.netPnl; bv = b.calc.netPnl; break;
        case 'rMultiple': av = a.calc.rMultiple ?? -Infinity; bv = b.calc.rMultiple ?? -Infinity; break;
        case 'pnlPercent': av = a.calc.pnlPercent ?? -Infinity; bv = b.calc.pnlPercent ?? -Infinity; break;
        case 'accountBalance': av = a.calc.accountBalance; bv = b.calc.accountBalance; break;
        default: av = 0; bv = 0;
      }
      if (av < bv) return sortDir === 'asc' ? -1 : 1;
      if (av > bv) return sortDir === 'asc' ? 1 : -1;
      return 0;
    });
    return sorted;
  }, [tradesWithCalc, search, filterSymbol, filterStrategy, filterDirection, filterWinLoss, dateFrom, dateTo, sortKey, sortDir]);

  useEffect(() => {
    const available = new Set(trades.map((trade) => trade.id));
    setSelectedIds((current) => {
      const next = new Set(Array.from(current).filter((id) => available.has(id)));
      return next.size === current.size ? current : next;
    });
  }, [trades]);

  const filteredIds = useMemo(() => filtered.map((trade) => trade.id), [filtered]);
  const allFilteredSelected = filteredIds.length > 0 && filteredIds.every((id) => selectedIds.has(id));
  const someFilteredSelected = filteredIds.some((id) => selectedIds.has(id));

  const toggleTradeSelection = (id: string, checked: boolean) => {
    setSelectedIds((current) => {
      const next = new Set(current);
      if (checked) next.add(id);
      else next.delete(id);
      return next;
    });
  };

  const toggleFilteredSelection = (checked: boolean) => {
    setSelectedIds((current) => {
      const next = new Set(current);
      for (const id of filteredIds) {
        if (checked) next.add(id);
        else next.delete(id);
      }
      return next;
    });
  };

  const changeEditMode = (enabled: boolean) => {
    setEditMode(enabled);
    if (!enabled) {
      setSelectedIds(new Set());
      setBulkDeleteOpen(false);
    }
  };

  const toggleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    } else {
      setSortKey(key);
      setSortDir('desc');
    }
  };

  const handleSave = async (values: TradeFormValues) => {
    if (editing) {
      await updateTrade(editing.id, values);
    } else {
      await addTrade(values);
    }
    setEditing(null);
  };

  const handleExport = () => {
    const csv = tradesWithCalcToCsv(tradesWithCalc);
    downloadCsv(`trade-journal-${new Date().toISOString().slice(0, 10)}.csv`, csv);
  };

  const handleImportClick = () => fileRef.current?.click();

  const handleImport = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setImportMsg(null);
    setImporting(true);
    try {
      // Spreadsheet support is relatively large, so load it only when a user imports.
      const { parseTradeHistoryFile } = await import('@/lib/trade-history-import');
      const firstNumber = Math.max(0, ...trades.map((trade) => trade.trade_number)) + 1;
      const history = await parseTradeHistoryFile(file, firstNumber);
      const existingPositions = new Set(
        trades.map((trade) => trade.broker_position_id).filter((id): id is string => Boolean(id))
      );
      let imported = 0;
      let duplicates = 0;
      let failed = 0;
      const importedTrades: Trade[] = [];
      for (const input of history.trades) {
        if (input.broker_position_id && existingPositions.has(input.broker_position_id)) {
          duplicates++;
          continue;
        }
        try {
          const savedTrade = await addTrade(input);
          importedTrades.push(savedTrade);
          imported++;
          if (input.broker_position_id) existingPositions.add(input.broker_position_id);
        } catch {
          failed++;
        }
      }
      let backupMessage = '';
      try {
        const { saveAndDownloadJournalBackup } = await import('@/lib/local-journal-backup');
        const createdAt = new Date().toISOString();
        await saveAndDownloadJournalBackup({
          schema_version: 1,
          backup_type: 'post-report-import',
          created_at: createdAt,
          source_report: {
            name: file.name,
            type: file.type || 'application/octet-stream',
            size: file.size,
            last_modified: new Date(file.lastModified).toISOString(),
            detected_format: history.format,
          },
          import_result: {
            parsed: history.trades.length,
            imported,
            duplicates,
            failed,
            warnings: history.warnings,
          },
          database: {
            profile,
            settings,
            strategies,
            trades: [...trades, ...importedTrades],
          },
        });
        backupMessage = 'A JSON database backup was saved in this browser and downloaded.';
      } catch {
        backupMessage = 'Trades were imported, but the local JSON backup could not be created.';
      }
      const details = [
        `${history.format}: imported ${imported} of ${history.trades.length} trades.`,
        duplicates ? `${duplicates} duplicate${duplicates === 1 ? '' : 's'} skipped.` : '',
        failed ? `${failed} row${failed === 1 ? '' : 's'} failed to save.` : '',
        history.warnings.length ? `${history.warnings.length} parsing warning${history.warnings.length === 1 ? '' : 's'}.` : '',
        backupMessage,
      ].filter(Boolean).join(' ');
      setImportMsg(details);
    } catch (error) {
      setImportMsg(error instanceof Error ? error.message : 'Could not read that trade-history file.');
    } finally {
      setImporting(false);
      e.target.value = '';
    }
  };

  const confirmDelete = async () => {
    if (!deleteId) return;
    setDeleting(true);
    try {
      await deleteTrade(deleteId);
      setSelectedIds((current) => {
        const next = new Set(current);
        next.delete(deleteId);
        return next;
      });
      setDeleteId(null);
    } finally {
      setDeleting(false);
    }
  };

  const confirmBulkDelete = async () => {
    const ids = Array.from(selectedIds);
    if (!ids.length) return;
    setDeleting(true);
    try {
      await deleteTrades(ids);
      setSelectedIds(new Set());
      setBulkDeleteOpen(false);
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Trade Log</h1>
          <p className="text-sm text-muted-foreground">
            {filtered.length} of {tradesWithCalc.length} trades
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {editMode && selectedIds.size > 0 && (
            <>
              <span className="text-xs font-medium text-muted-foreground">
                {selectedIds.size} selected
              </span>
              <Button variant="destructive" size="sm" onClick={() => setBulkDeleteOpen(true)}>
                <Trash2 className="mr-1.5 h-3.5 w-3.5" /> Delete selected
              </Button>
            </>
          )}
          <Button variant="outline" size="sm" onClick={handleImportClick} disabled={importing}>
            <Upload className="mr-1.5 h-3.5 w-3.5" /> {importing ? 'Importing…' : 'Upload history'}
          </Button>
          <input
            ref={fileRef}
            type="file"
            accept=".xls,.xlsx,.csv,.ods,.fods,.html,.htm,.pdf,.odp"
            className="hidden"
            onChange={handleImport}
          />
          <Button variant="outline" size="sm" onClick={handleExport} disabled={tradesWithCalc.length === 0}>
            <Download className="mr-1.5 h-3.5 w-3.5" /> Export
          </Button>
          <Button size="sm" onClick={() => { setEditing(null); setFormOpen(true); }}>
            <Plus className="mr-1.5 h-3.5 w-3.5" /> Add Trade
          </Button>
        </div>
      </div>

      {importMsg && (
        <div className="rounded-md border border-border bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
          <FileText className="mr-1.5 inline h-3.5 w-3.5" />
          {importMsg}
        </div>
      )}

      <Card className="flex items-start gap-3 border-dashed p-4">
        <FileSpreadsheet className="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
        <div>
          <p className="text-sm font-medium">Import without the MT5 connector</p>
          <p className="mt-0.5 text-xs text-muted-foreground">
            Upload an Exness/MetaTrader closed-position report, a cTrader account-statement PDF/HTML file, or a journal spreadsheet in XLS, XLSX, CSV, ODS, FODS, or HTML format. All imported trades are saved into the same journal history table.
          </p>
        </div>
      </Card>

      <Card className="p-3">
        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
          <div className="relative xl:col-span-2">
            <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search notes, lessons, symbol…"
              className="pl-8"
            />
          </div>
          <FilterSelect label="Symbol" value={filterSymbol} onChange={setFilterSymbol} options={symbols} />
          <FilterSelect label="Strategy" value={filterStrategy} onChange={setFilterStrategy} options={strategies.map((s) => s.name)} />
          <FilterSelect label="Direction" value={filterDirection} onChange={setFilterDirection} options={['Long', 'Short']} />
          <FilterSelect label="Result" value={filterWinLoss} onChange={setFilterWinLoss} options={['Win', 'Loss', 'Breakeven']} />
          <div>
            <Label className="sr-only">From date</Label>
            <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
          </div>
          <div>
            <Label className="sr-only">To date</Label>
            <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
          </div>
          <div className="flex h-9 items-center justify-between gap-3 rounded-md border border-input bg-background px-3">
            <Label htmlFor="trade-edit-mode" className="cursor-pointer whitespace-nowrap text-xs font-medium">
              Edit mode
            </Label>
            <div className="flex items-center gap-2">
              <span className="text-[11px] text-muted-foreground">{editMode ? 'On' : 'Off'}</span>
              <Switch
                id="trade-edit-mode"
                checked={editMode}
                onCheckedChange={changeEditMode}
                aria-label="Show trade selection and edit controls"
              />
            </div>
          </div>
          {(search || filterSymbol !== 'all' || filterStrategy !== 'all' || filterDirection !== 'all' || filterWinLoss !== 'all' || dateFrom || dateTo) && (
            <Button variant="ghost" size="sm" onClick={() => { setSearch(''); setFilterSymbol('all'); setFilterStrategy('all'); setFilterDirection('all'); setFilterWinLoss('all'); setDateFrom(''); setDateTo(''); }}>
              Clear filters
            </Button>
          )}
        </div>
      </Card>

      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full min-w-[1100px] text-sm">
          <thead className="sticky top-0 bg-muted/60 backdrop-blur-sm">
            <tr className="border-b border-border">
              {editMode && (
                <th className="w-10 px-3 py-2.5 text-left">
                  <Checkbox
                    checked={allFilteredSelected ? true : someFilteredSelected ? 'indeterminate' : false}
                    onCheckedChange={(checked) => toggleFilteredSelection(checked === true)}
                    aria-label={allFilteredSelected ? 'Deselect all filtered trades' : 'Select all filtered trades'}
                  />
                </th>
              )}
              {COLUMNS.map((col, i) => {
                const isSort = col.key !== 'static';
                return (
                  <th
                    key={i}
                    className={cn('px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-muted-foreground', col.className)}
                  >
                    {isSort ? (
                      <button
                        onClick={() => toggleSort(col.key as SortKey)}
                        className="inline-flex items-center gap-1 hover:text-foreground"
                      >
                        {col.label}
                        <SortIcon active={sortKey === col.key} dir={sortDir} />
                      </button>
                    ) : (
                      col.label
                    )}
                  </th>
                );
              })}
              {editMode && <th className="w-20 px-3 py-2.5"><span className="sr-only">Trade actions</span></th>}
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 ? (
              <tr>
                <td colSpan={COLUMNS.length + (editMode ? 2 : 0)} className="px-3 py-16 text-center text-sm text-muted-foreground">
                  No trades match your filters. Click <span className="font-medium text-foreground">Add Trade</span> to get started.
                </td>
              </tr>
            ) : (
              filtered.map((t) => (
                <TradeRow
                  key={t.id}
                  trade={t}
                  onEdit={() => { setEditing(t); setFormOpen(true); }}
                  onDelete={() => setDeleteId(t.id)}
                  selected={selectedIds.has(t.id)}
                  onSelectedChange={(checked) => toggleTradeSelection(t.id, checked)}
                  editMode={editMode}
                  threshold={settings.risk_warning_threshold}
                />
              ))
            )}
          </tbody>
        </table>
      </div>

      <TradeForm
        open={formOpen}
        onOpenChange={setFormOpen}
        strategies={strategies}
        trades={trades}
        editing={editing}
        onSave={handleSave}
      />

      <Dialog open={!!deleteId} onOpenChange={(o) => !o && setDeleteId(null)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete trade?</DialogTitle>
            <DialogDescription>This trade will be permanently removed from your journal. This can&apos;t be undone.</DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId(null)} disabled={deleting}>Cancel</Button>
            <Button variant="destructive" onClick={confirmDelete} disabled={deleting}>
              {deleting ? 'Deleting…' : 'Delete'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={bulkDeleteOpen} onOpenChange={(open) => !deleting && setBulkDeleteOpen(open)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete {selectedIds.size} selected trades?</DialogTitle>
            <DialogDescription>
              These trades will be permanently removed from your journal. This can&apos;t be undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setBulkDeleteOpen(false)} disabled={deleting}>Cancel</Button>
            <Button variant="destructive" onClick={confirmBulkDelete} disabled={deleting || selectedIds.size === 0}>
              {deleting ? 'Deleting…' : `Delete ${selectedIds.size} trades`}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function TradeRow({
  trade,
  onEdit,
  onDelete,
  selected,
  onSelectedChange,
  editMode,
  threshold,
}: {
  trade: TradeWithCalc;
  onEdit: () => void;
  onDelete: () => void;
  selected: boolean;
  onSelectedChange: (checked: boolean) => void;
  editMode: boolean;
  threshold: number;
}) {
  const c = trade.calc;
  const isWin = c.winLoss === 'Win';
  const isLoss = c.winLoss === 'Loss';
  const rowBg = isWin
    ? 'bg-emerald-500/[0.04] hover:bg-emerald-500/[0.08]'
    : isLoss
    ? 'bg-red-500/[0.04] hover:bg-red-500/[0.08]'
    : 'hover:bg-muted/40';

  return (
    <tr className={cn('border-b border-border/50 transition-colors', rowBg, editMode && selected && 'bg-primary/[0.08] hover:bg-primary/[0.12]')}>
      {editMode && (
        <td className="w-10 px-3 py-2.5">
          <Checkbox
            checked={selected}
            onCheckedChange={(checked) => onSelectedChange(checked === true)}
            aria-label={`Select trade ${trade.trade_number}`}
          />
        </td>
      )}
      <td className="px-3 py-2.5 tabular-nums text-muted-foreground">{trade.trade_number}</td>
      <td className="whitespace-nowrap px-3 py-2.5 text-muted-foreground">{fmtDate(trade.date)}</td>
      <td className="px-3 py-2.5 font-medium">
        <div>{trade.symbol}</div>
        {trade.source === 'mt5' && <span className="text-[9px] font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">Exness MT5</span>}
      </td>
      <td className="px-3 py-2.5">
        <span className={cn('text-xs font-semibold', trade.direction === 'Long' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400')}>
          {trade.direction === 'Long' ? 'L' : 'S'}
        </span>
      </td>
      <td className="px-3 py-2.5 text-muted-foreground">
        <div>{trade.strategy}</div>
        {!!trade.emotions?.length && (
          <div className="mt-1 flex max-w-40 flex-wrap gap-1">
            {trade.emotions.slice(0, 3).map((emotion) => (
              <Badge key={emotion} variant="outline" className="px-1.5 py-0 text-[9px] font-normal">
                {emotion}
              </Badge>
            ))}
            {trade.emotions.length > 3 && (
              <span className="text-[9px] text-muted-foreground">+{trade.emotions.length - 3}</span>
            )}
          </div>
        )}
      </td>
      <td className="px-3 py-2.5 tabular-nums">{fmtPrice(trade.entry_price)}</td>
      <td className="px-3 py-2.5 tabular-nums">{fmtPrice(trade.exit_price)}</td>
      <td className="px-3 py-2.5 tabular-nums text-muted-foreground">{fmtMoney(c.riskDollars)}</td>
      <td className="px-3 py-2.5 tabular-nums">
        {c.riskPercent != null ? (
          <span className={cn('inline-flex items-center gap-1', c.riskFlagged && 'text-red-600 dark:text-red-400 font-medium')}>
            {c.riskFlagged && <AlertTriangle className="h-3 w-3" />}
            {fmtPct(c.riskPercent, 2)}
          </span>
        ) : '—'}
      </td>
      <td className="px-3 py-2.5 tabular-nums text-muted-foreground">{c.pipsGainedLost != null ? fmtNum(c.pipsGainedLost, 1) : '—'}</td>
      <td className="px-3 py-2.5 tabular-nums text-muted-foreground">{fmtMoney(c.grossPnl, { sign: true })}</td>
      <td className={cn('px-3 py-2.5 font-semibold tabular-nums', moneyColor(c.netPnl))}>{fmtMoney(c.netPnl, { sign: true })}</td>
      <td className={cn('px-3 py-2.5 tabular-nums', moneyColor(c.rMultiple ?? 0))}>{c.rMultiple != null ? fmtNum(c.rMultiple) : '—'}</td>
      <td className={cn('px-3 py-2.5 tabular-nums', moneyColor(c.pnlPercent ?? 0))}>{c.pnlPercent != null ? fmtPct(c.pnlPercent, 2) : '—'}</td>
      <td className="px-3 py-2.5 tabular-nums">{fmtMoney(c.accountBalance)}</td>
      <td className="px-3 py-2.5"><WinLossBadge wl={c.winLoss} /></td>
      {editMode && (
        <td className="px-3 py-2.5">
          <div className="flex items-center gap-1">
            <button onClick={onEdit} className="flex h-7 w-7 items-center justify-center rounded text-muted-foreground hover:bg-muted hover:text-foreground" aria-label="Edit">
              <Pencil className="h-3.5 w-3.5" />
            </button>
            <button onClick={onDelete} className="flex h-7 w-7 items-center justify-center rounded text-muted-foreground hover:bg-destructive/10 hover:text-destructive" aria-label="Delete">
              <Trash2 className="h-3.5 w-3.5" />
            </button>
          </div>
        </td>
      )}
    </tr>
  );
}

function WinLossBadge({ wl }: { wl: WinLoss }) {
  const styles: Record<WinLoss, string> = {
    Win: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    Loss: 'bg-red-500/15 text-red-600 dark:text-red-400',
    Breakeven: 'bg-muted text-muted-foreground',
  };
  return <Badge variant="secondary" className={cn('text-[10px] font-semibold', styles[wl])}>{wl === 'Breakeven' ? 'BE' : wl}</Badge>;
}

function SortIcon({ active, dir }: { active: boolean; dir: 'asc' | 'desc' }) {
  if (!active) return <ArrowUpDown className="h-3 w-3 opacity-40" />;
  return dir === 'asc' ? <ArrowUp className="h-3 w-3" /> : <ArrowDown className="h-3 w-3" />;
}

function FilterSelect({
  label,
  value,
  onChange,
  options,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  options: string[];
}) {
  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger className="h-9"><SelectValue placeholder={label} /></SelectTrigger>
      <SelectContent>
        <SelectItem value="all">All {label.toLowerCase()}</SelectItem>
        {options.map((o) => <SelectItem key={o} value={o}>{o}</SelectItem>)}
      </SelectContent>
    </Select>
  );
}
