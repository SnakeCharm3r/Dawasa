import { NextResponse } from 'next/server';
import { ZodError } from 'zod';
import { UnauthorizedError } from './auth';

export function apiError(error: unknown) {
  if (error instanceof UnauthorizedError) {
    return NextResponse.json({ error: error.message }, { status: 401 });
  }
  if (error instanceof ZodError) {
    return NextResponse.json(
      { error: 'Invalid request payload.', details: error.flatten() },
      { status: 400 }
    );
  }
  const message = error instanceof Error ? error.message : 'Unexpected server error.';
  console.error(JSON.stringify({ event: 'api_error', message }));
  return NextResponse.json({ error: message }, { status: 500 });
}
