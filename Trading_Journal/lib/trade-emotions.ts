export const GENERIC_TRADE_EMOTIONS = [
  'Greed',
  'Fear',
  'Confidence',
  'Speculation',
  'Calm',
  'Anxiety',
  'FOMO',
  'Hesitation',
  'Frustration',
  'Revenge',
] as const;

export const MAX_TRADE_EMOTIONS = 12;
export const MAX_TRADE_EMOTION_LENGTH = 32;

export function normalizeTradeEmotions(emotions: string[]): string[] {
  const normalized: string[] = [];
  const seen = new Set<string>();

  for (const value of emotions) {
    const emotion = value.trim().slice(0, MAX_TRADE_EMOTION_LENGTH);
    const key = emotion.toLocaleLowerCase();
    if (!emotion || seen.has(key)) continue;

    seen.add(key);
    normalized.push(emotion);
    if (normalized.length === MAX_TRADE_EMOTIONS) break;
  }

  return normalized;
}

export function toggleTradeEmotion(emotions: string[], emotion: string): string[] {
  const cleanEmotion = emotion.trim().slice(0, MAX_TRADE_EMOTION_LENGTH);
  if (!cleanEmotion) return normalizeTradeEmotions(emotions);

  const key = cleanEmotion.toLocaleLowerCase();
  const exists = emotions.some((value) => value.trim().toLocaleLowerCase() === key);
  return exists
    ? emotions.filter((value) => value.trim().toLocaleLowerCase() !== key)
    : normalizeTradeEmotions([...emotions, cleanEmotion]);
}
