import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { requireAdmin } from '@/lib/server/admin-auth';
import { UnauthorizedError } from '@/lib/server/auth';
import { listLoginActivity } from '@/lib/server/login-activity';

export const dynamic = 'force-dynamic';

const filtersSchema = z.object({
  search: z.string().trim().max(200).optional(),
  event_type: z.enum(['registered', 'login', 'logout', 'failed_login']).optional(),
  date_from: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  date_to: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  page: z.coerce.number().int().min(1).default(1),
  page_size: z.coerce.number().int().min(10).max(100).default(25),
});

export async function GET(request: NextRequest) {
  try {
    await requireAdmin(request);
    const raw = Object.fromEntries(request.nextUrl.searchParams.entries());
    const parsed = filtersSchema.safeParse(raw);
    if (!parsed.success) {
      return NextResponse.json({ error: 'Invalid activity filters.' }, { status: 400 });
    }
    const input = parsed.data;
    const result = await listLoginActivity({
      search: input.search,
      eventType: input.event_type,
      dateFrom: input.date_from,
      dateTo: input.date_to,
      page: input.page,
      pageSize: input.page_size,
    });
    return NextResponse.json({
      activities: result.rows,
      pagination: {
        page: input.page,
        page_size: input.page_size,
        total: result.total,
        total_pages: Math.max(1, Math.ceil(result.total / input.page_size)),
      },
    }, { headers: { 'Cache-Control': 'private, no-store' } });
  } catch (error) {
    if (error instanceof UnauthorizedError) {
      return NextResponse.json({ error: error.message }, { status: 403 });
    }
    console.error('Failed to list login activity', error);
    return NextResponse.json({ error: 'Could not load login activity.' }, { status: 500 });
  }
}
