export type Direction = 'Long' | 'Short';
export type CalcMode = 'pips' | 'shares';
export type WinLoss = 'Win' | 'Loss' | 'Breakeven';

export interface Settings {
  id: string;
  starting_balance: number;
  pip_size: number;
  pip_value_per_lot: number;
  risk_warning_threshold: number;
  idle_timeout_minutes: number;
  updated_at: string;
}

export interface Strategy {
  id: string;
  name: string;
  created_at: string;
}

export interface Profile {
  id: string;
  email: string | null;
  username: string | null;
  display_name: string | null;
  avatar_url: string | null;
  country: string | null;
  registered_ip?: string | null;
  registered_country?: string | null;
  registered_city?: string | null;
  registered_at?: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Trade {
  id: string;
  trade_number: number;
  date: string;
  symbol: string;
  direction: Direction;
  strategy: string;
  calc_mode: CalcMode;
  entry_price: number;
  exit_price: number;
  lot_size: number;
  fees: number;
  stop_loss: number | null;
  target_price: number | null;
  setup_notes: string | null;
  lessons_learned: string | null;
  exit_reason?: string | null;
  emotion_during_trade?: string | null;
  emotions?: string[];
  created_at: string;
  updated_at: string;
  user_id?: string | null;
  trading_account_id?: string | null;
  broker_deal_id?: string | null;
  broker_order_id?: string | null;
  broker_position_id?: string | null;
  broker_deal_ids?: string[];
  side?: 'buy' | 'sell' | null;
  volume?: number | null;
  open_time?: string | null;
  close_time?: string | null;
  take_profit?: number | null;
  commission?: number;
  swap?: number;
  fee?: number;
  gross_profit?: number | null;
  net_profit?: number | null;
  magic_number?: number | null;
  broker_comment?: string | null;
  source?: 'manual' | 'mt5';
  raw_broker_metadata?: Record<string, unknown>;
}

export type SyncStatus =
  | 'connected'
  | 'syncing'
  | 'failed'
  | 'disconnected'
  | 'terminal_offline';

export interface TradingAccount {
  id: string;
  broker: string;
  platform: 'MT5';
  account_number: string;
  server: string;
  account_name: string | null;
  account_currency: string | null;
  account_type: string | null;
  balance: number | null;
  equity: number | null;
  leverage: number | null;
  last_sync_at: string | null;
  sync_status: SyncStatus;
  sync_error: string | null;
  paired_at?: string | null;
  pairing_expires_at?: string | null;
  imported_trade_count: number;
}

export interface TradeInput {
  trade_number: number;
  date: string;
  symbol: string;
  direction: Direction;
  strategy: string;
  calc_mode: CalcMode;
  entry_price: number;
  exit_price: number;
  lot_size: number;
  fees: number;
  stop_loss: number | null;
  target_price: number | null;
  setup_notes: string | null;
  lessons_learned: string | null;
  exit_reason?: string | null;
  emotion_during_trade?: string | null;
  emotions?: string[];
}

export interface TradeCalc {
  riskPips: number | null;
  riskDollars: number | null;
  riskPercent: number | null;
  rewardPips: number | null;
  rewardDollars: number | null;
  pipsGainedLost: number | null;
  grossPnl: number;
  netPnl: number;
  rMultiple: number | null;
  pnlPercent: number | null;
  accountBalance: number;
  winLoss: WinLoss;
  riskFlagged: boolean;
}

export interface TradeWithCalc extends Trade {
  calc: TradeCalc;
}
