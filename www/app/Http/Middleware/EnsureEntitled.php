<?php
// app/Http/Middleware/EnsureEntitled.php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureEntitled {
    public function handle($request, Closure $next) {
        $u = Auth::user();
        if (!$u || !$u->entitled) {
            return response()->json(['error'=>'payment_required'], 402);
        }
        return $next($request);
    }
}
