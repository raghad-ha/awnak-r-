<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSocialAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success'=>false,'message'=>'Unauthenticated.'], 401);
        }

        if ($user->status !== 'approved') {
            return response()->json(['success'=>false,'message'=>'Only approved users can use social features.'], 403);
        }

        if (!in_array($user->type, ['volunteer', 'organization'])) {
            return response()->json(['success'=>false,'message'=>'This user type cannot use social features.'], 403);
        }

        return $next($request);
    }
}
