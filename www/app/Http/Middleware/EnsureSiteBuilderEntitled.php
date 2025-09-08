<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnsureSiteBuilderEntitled
{
    public function handle(Request $request, Closure $next)
    {
//        $user = $request->user();
//        if (!$user) {
//            return redirect()->route('/login');
//        }
//        if (!Gate::forUser($user)->allows('site.build')) {
//            // 権限なし → 支払いページへ
//            return redirect()->route('site.paywall');
//        }
//        if (Gate::allows('site.build', $user)) {
//            return $next($request);
//        }
//
//        if ($request->expectsJson()) {
//            // XHR の場合は 402/403 を返す
//            return response()->json(['ok' => false, 'reason' => 'PAYWALL'], 402);
//        }
//        return $next($request);
        $u = $request->user();
        if (!$u) return redirect()->route('login');

        if ($u->role === 'admin' || ($u->role === 'lawyer' && $u->account_type === 'pro')) {
            return $next($request);
        }
        return redirect()->route('site.paywall');
    }

}
