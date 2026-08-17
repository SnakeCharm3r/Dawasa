<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierEmailVerificationController extends Controller
{
    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('supplier'), 403);
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return response()->json(['message' => 'Supplier email verified successfully.']);
    }

    public function resend(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('supplier'), 403);
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.']);
        }
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification email sent.']);
    }
}
