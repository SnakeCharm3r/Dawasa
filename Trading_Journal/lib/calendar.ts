export interface CalendarDateCell {
  date: Date;
  dateKey: string;
  inMonth: boolean;
}

function padDatePart(value: number): string {
  return String(value).padStart(2, '0');
}

/**
 * Formats a Date as a calendar date in the user's local timezone.
 *
 * Using toISOString() here would convert local midnight to UTC and can move
 * the key to the previous day in positive-offset timezones such as EAT.
 */
export function toLocalDateKey(date: Date): string {
  return `${date.getFullYear()}-${padDatePart(date.getMonth() + 1)}-${padDatePart(date.getDate())}`;
}

export function buildMonthDates(year: number, month: number): CalendarDateCell[] {
  const firstOfMonth = new Date(year, month, 1);
  const mondayOffset = (firstOfMonth.getDay() + 6) % 7;
  const start = new Date(year, month, 1 - mondayOffset);

  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(start);
    date.setDate(start.getDate() + index);

    return {
      date,
      dateKey: toLocalDateKey(date),
      inMonth: date.getFullYear() === year && date.getMonth() === month,
    };
  });
}
