export const BROKER_IDS = [
  'Exness',
  'IC Markets',
  'Pepperstone',
  'XM',
  'HFM',
  'FXTM',
  'FP Markets',
  'Tickmill',
  'Admirals',
  'Other',
] as const;

export type BrokerId = (typeof BROKER_IDS)[number];
