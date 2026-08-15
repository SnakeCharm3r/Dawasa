import type {
  CalcMode,
  Direction,
  Settings,
  Trade,
  TradeCalc,
  TradeWithCalc,
  WinLoss,
} from './types';

export function round(value: number, decimals = 2): number {
  const f = Math.pow(10, decimals);
  return Math.round((value + Number.EPSILON) * f) / f;
}

function classifyWinLoss(netPnl: number): WinLoss {
  if (netPnl > 0) return 'Win';
  if (netPnl < 0) return 'Loss';
  return 'Breakeven';
}

export function calcTrade(trade: Trade, accountBalance: number, settings: Settings): TradeCalc {
  const { calc_mode, direction, entry_price, exit_price, lot_size, fees, stop_loss, target_price } = trade;
  const { pip_size, pip_value_per_lot, risk_warning_threshold } = settings;

  const isPips = calc_mode === 'pips';
  const isLong = direction === 'Long';

  const sign = isLong ? 1 : -1;

  // Pips / price difference gained/lost
  const diff = (exit_price - entry_price) * sign;
  const pipsGainedLost = isPips ? diff / pip_size : null;

  // Gross P&L
  const calculatedGrossPnl = isPips
    ? (pipsGainedLost as number) * pip_value_per_lot * lot_size
    : diff * lot_size;
  // Broker imports already include instrument-specific contract sizing and costs.
  // Prefer those authoritative figures over the journal's global pip settings.
  const grossPnl = trade.source === 'mt5' && trade.gross_profit != null
    ? trade.gross_profit
    : calculatedGrossPnl;
  const netPnl = trade.source === 'mt5' && trade.net_profit != null
    ? trade.net_profit
    : grossPnl - (fees || 0);

  // Risk / reward
  let riskPips: number | null = null;
  let riskDollars: number | null = null;
  if (stop_loss != null) {
    if (isPips) {
      riskPips = Math.abs(entry_price - stop_loss) / pip_size;
      riskDollars = riskPips * pip_value_per_lot * lot_size;
    } else {
      riskDollars = Math.abs(entry_price - stop_loss) * lot_size;
    }
  }

  let rewardPips: number | null = null;
  let rewardDollars: number | null = null;
  if (target_price != null) {
    if (isPips) {
      rewardPips = Math.abs(target_price - entry_price) / pip_size;
      rewardDollars = rewardPips * pip_value_per_lot * lot_size;
    } else {
      rewardDollars = Math.abs(target_price - entry_price) * lot_size;
    }
  }

  const riskPercent = riskDollars != null && accountBalance > 0
    ? (riskDollars / accountBalance) * 100
    : null;
  const pnlPercent = accountBalance > 0 ? (netPnl / accountBalance) * 100 : null;

  const rMultiple = riskDollars != null && riskDollars !== 0 ? netPnl / riskDollars : null;

  const winLoss = classifyWinLoss(netPnl);
  const riskFlagged = riskPercent != null && riskPercent > risk_warning_threshold;

  const updatedBalance = accountBalance + netPnl;

  return {
    riskPips: riskPips != null ? round(riskPips, 1) : null,
    riskDollars: riskDollars != null ? round(riskDollars) : null,
    riskPercent: riskPercent != null ? round(riskPercent, 2) : null,
    rewardPips: rewardPips != null ? round(rewardPips, 1) : null,
    rewardDollars: rewardDollars != null ? round(rewardDollars) : null,
    pipsGainedLost: pipsGainedLost != null ? round(pipsGainedLost, 1) : null,
    grossPnl: round(grossPnl),
    netPnl: round(netPnl),
    rMultiple: rMultiple != null ? round(rMultiple, 2) : null,
    pnlPercent: pnlPercent != null ? round(pnlPercent, 2) : null,
    accountBalance: round(updatedBalance),
    winLoss,
    riskFlagged,
  };
}

export function calcTrades(trades: Trade[], settings: Settings): TradeWithCalc[] {
  const sorted = [...trades].sort((a, b) => {
    const d = a.date.localeCompare(b.date);
    return d !== 0 ? d : a.trade_number - b.trade_number;
  });

  let balance = settings.starting_balance;
  return sorted.map((trade) => {
    const calc = calcTrade(trade, balance, settings);
    balance = calc.accountBalance;
    return { ...trade, calc };
  });
}

export interface JournalStats {
  totalTrades: number;
  wins: number;
  losses: number;
  breakevens: number;
  winRate: number;
  totalNetPnl: number;
  currentBalance: number;
  averageWin: number;
  averageLoss: number;
  largestWin: number;
  largestLoss: number;
  profitFactor: number;
  averageRMultiple: number | null;
  averageRiskPercent: number | null;
}

export function computeStats(trades: TradeWithCalc[], settings: Settings): JournalStats {
  const totalTrades = trades.length;
  if (totalTrades === 0) {
    return {
      totalTrades: 0,
      wins: 0,
      losses: 0,
      breakevens: 0,
      winRate: 0,
      totalNetPnl: 0,
      currentBalance: settings.starting_balance,
      averageWin: 0,
      averageLoss: 0,
      largestWin: 0,
      largestLoss: 0,
      profitFactor: 0,
      averageRMultiple: null,
      averageRiskPercent: null,
    };
  }

  let wins = 0;
  let losses = 0;
  let breakevens = 0;
  let grossWins = 0;
  let grossLosses = 0;
  let largestWin = 0;
  let largestLoss = 0;
  let rSum = 0;
  let rCount = 0;
  let riskSum = 0;
  let riskCount = 0;
  let totalNetPnl = 0;

  for (const t of trades) {
    const { netPnl, rMultiple, riskPercent } = t.calc;
    totalNetPnl += netPnl;
    if (netPnl > 0) {
      wins++;
      grossWins += netPnl;
      largestWin = Math.max(largestWin, netPnl);
    } else if (netPnl < 0) {
      losses++;
      grossLosses += Math.abs(netPnl);
      largestLoss = Math.min(largestLoss, netPnl);
    } else {
      breakevens++;
    }
    if (rMultiple != null) {
      rSum += rMultiple;
      rCount++;
    }
    if (riskPercent != null) {
      riskSum += riskPercent;
      riskCount++;
    }
  }

  const currentBalance = settings.starting_balance + totalNetPnl;

  return {
    totalTrades,
    wins,
    losses,
    breakevens,
    winRate: round((wins / totalTrades) * 100, 1),
    totalNetPnl: round(totalNetPnl),
    currentBalance: round(currentBalance),
    averageWin: wins > 0 ? round(grossWins / wins) : 0,
    averageLoss: losses > 0 ? round(grossLosses / losses) : 0,
    largestWin: round(largestWin),
    largestLoss: round(largestLoss),
    profitFactor: grossLosses > 0 ? round(grossWins / grossLosses, 2) : grossWins > 0 ? Infinity : 0,
    averageRMultiple: rCount > 0 ? round(rSum / rCount, 2) : null,
    averageRiskPercent: riskCount > 0 ? round(riskSum / riskCount, 2) : null,
  };
}

export function validateStopLoss(
  direction: Direction,
  entryPrice: number,
  stopLoss: number
): boolean {
  if (direction === 'Long') return stopLoss < entryPrice;
  return stopLoss > entryPrice;
}

export function nextTradeNumber(trades: Trade[]): number {
  if (trades.length === 0) return 1;
  return Math.max(...trades.map((t) => t.trade_number)) + 1;
}

export type { CalcMode, Direction, WinLoss };
