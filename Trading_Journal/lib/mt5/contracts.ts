import { z } from 'zod';
import { BROKER_IDS } from '@/lib/brokers';

const optionalNumber = z.number().finite().nullable().optional();
const optionalText = z.string().max(500).nullable().optional();

export const tradingAccountInputSchema = z.object({
  broker: z.enum(BROKER_IDS),
  account_number: z.string().trim().min(1).max(40),
  password: z.string().min(1).max(256),
  server: z.string().trim().min(1).max(120),
  account_name: z.string().trim().max(120).nullable().optional(),
  account_currency: z.string().trim().max(12).nullable().optional(),
  account_type: z.string().trim().max(60).nullable().optional(),
  history_days: z.union([z.literal(30), z.literal(90), z.literal(180), z.literal(365)]).default(90),
});

export const normalizedDealSchema = z.object({
  broker_deal_id: z.string().min(1),
  broker_order_id: z.string().nullable().optional(),
  broker_position_id: z.string().min(1),
  entry_type: z.enum(['in', 'out', 'inout', 'out_by']),
  deal_type: z.string().min(1),
  symbol: z.string().min(1),
  side: z.enum(['buy', 'sell']).nullable().optional(),
  deal_time: z.string().datetime({ offset: true }),
  price: z.number().finite().nonnegative(),
  volume: z.number().finite().nonnegative(),
  profit: z.number().finite().default(0),
  commission: z.number().finite().default(0),
  swap: z.number().finite().default(0),
  fee: z.number().finite().default(0),
  magic_number: z.number().int().nullable().optional(),
  comment: optionalText,
  raw_metadata: z.record(z.unknown()).default({}),
});

export const normalizedTradeSchema = z.object({
  broker_position_id: z.string().min(1),
  broker_order_id: z.string().nullable().optional(),
  broker_deal_id: z.string().nullable().optional(),
  broker_deal_ids: z.array(z.string().min(1)).min(2),
  symbol: z.string().min(1),
  side: z.enum(['buy', 'sell']),
  volume: z.number().finite().positive(),
  entry_price: z.number().finite().nonnegative(),
  exit_price: z.number().finite().nonnegative(),
  open_time: z.string().datetime({ offset: true }),
  close_time: z.string().datetime({ offset: true }),
  stop_loss: optionalNumber,
  take_profit: optionalNumber,
  commission: z.number().finite().default(0),
  swap: z.number().finite().default(0),
  fee: z.number().finite().default(0),
  gross_profit: z.number().finite(),
  net_profit: z.number().finite(),
  magic_number: z.number().int().nullable().optional(),
  comment: optionalText,
  raw_metadata: z.record(z.unknown()).default({}),
});

export const accountSnapshotSchema = z.object({
  login: z.string().optional(),
  name: optionalText,
  server: optionalText,
  currency: z.string().max(12).nullable().optional(),
  leverage: z.number().int().positive().nullable().optional(),
  balance: optionalNumber,
  equity: optionalNumber,
  margin: optionalNumber,
  free_margin: optionalNumber,
  raw_metadata: z.record(z.unknown()).default({}),
});

export const mt5SyncPayloadSchema = z.object({
  trading_account_id: z.string().uuid(),
  account: accountSnapshotSchema,
  deals: z.array(normalizedDealSchema).max(10000),
  trades: z.array(normalizedTradeSchema).max(5000),
  last_deal_time: z.string().datetime({ offset: true }).nullable(),
  last_deal_ticket: z.string().nullable(),
  sync_status: z.enum(['connected', 'failed', 'terminal_offline']).default('connected'),
  sync_error: z.string().max(2000).nullable().default(null),
});

export type TradingAccountInput = z.infer<typeof tradingAccountInputSchema>;
export type Mt5SyncPayload = z.infer<typeof mt5SyncPayloadSchema>;

export const BROKER_TRADE_UPDATE_FIELDS = [
  'broker_deal_id',
  'broker_order_id',
  'broker_position_id',
  'broker_deal_ids',
  'symbol',
  'direction',
  'side',
  'volume',
  'entry_price',
  'exit_price',
  'lot_size',
  'open_time',
  'close_time',
  'date',
  'stop_loss',
  'target_price',
  'take_profit',
  'fees',
  'commission',
  'swap',
  'fee',
  'gross_profit',
  'net_profit',
  'magic_number',
  'broker_comment',
  'raw_broker_metadata',
] as const;
