<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->bearerToken() ?: $request->header('X-Api-Token');
        if (! $header) {
            return response()->json(['message' => 'Token API manquant.'], 401);
        }

        $token = ApiToken::query()->where('token_hash', ApiToken::hashToken($header))->first();
        if (! $token) {
            return response()->json(['message' => 'Token API invalide.'], 401);
        }

        $token->update(['last_used_at' => now()]);
        $request->attributes->set('api_company_id', $token->company_id);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
