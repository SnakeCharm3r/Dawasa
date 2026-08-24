import assert from 'node:assert/strict';
import test from 'node:test';
import {
  MAX_TRADE_EMOTIONS,
  normalizeTradeEmotions,
  toggleTradeEmotion,
} from '../lib/trade-emotions';

test('normalizes, de-duplicates, and removes empty emotions', () => {
  assert.deepEqual(normalizeTradeEmotions([' Fear ', 'fear', '', 'Confidence']), [
    'Fear',
    'Confidence',
  ]);
});

test('limits the number of emotions stored on a trade', () => {
  const emotions = Array.from({ length: MAX_TRADE_EMOTIONS + 3 }, (_, index) => `Emotion ${index}`);
  assert.equal(normalizeTradeEmotions(emotions).length, MAX_TRADE_EMOTIONS);
});

test('toggles emotions case-insensitively', () => {
  assert.deepEqual(toggleTradeEmotion(['Fear'], 'fear'), []);
  assert.deepEqual(toggleTradeEmotion(['Fear'], 'Confidence'), ['Fear', 'Confidence']);
});
