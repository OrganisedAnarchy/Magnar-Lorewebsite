<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Answer an AJAX request with JSON for the toast system, or fall back to a flash-message redirect.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function respond(Request $request, RedirectResponse $redirect, string $message, array $payload = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message] + $payload);
        }

        return $redirect->with('status', $message);
    }
}
