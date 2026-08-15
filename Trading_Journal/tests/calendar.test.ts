import assert from 'node:assert/strict';
import test from 'node:test';
import { buildMonthDates, toLocalDateKey } from '../lib/calendar';

test('uses local calendar dates without shifting EAT midnight to the previous UTC day', () => {
  const originalTimezone = process.env.TZ;
  process.env.TZ = 'Africa/Dar_es_Salaam';

  try {
    const localMidnight = new Date(2026, 6, 31);

    assert.equal(localMidnight.toISOString().slice(0, 10), '2026-07-30');
    assert.equal(toLocalDateKey(localMidnight), '2026-07-31');
  } finally {
    process.env.TZ = originalTimezone;
  }
});

test('places July 31, 2026 under Friday in a Monday-first calendar', () => {
  const originalTimezone = process.env.TZ;
  process.env.TZ = 'Africa/Dar_es_Salaam';

  try {
    const cells = buildMonthDates(2026, 6);
    const july31Index = cells.findIndex((cell) => cell.dateKey === '2026-07-31');

    assert.equal(cells[0].dateKey, '2026-06-29');
    assert.equal(july31Index % 7, 4);
    assert.equal(cells[july31Index].date.getDay(), 5);
  } finally {
    process.env.TZ = originalTimezone;
  }
});
