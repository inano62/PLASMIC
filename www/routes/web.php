<?php

use App\Http\Controllers\SiteController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// …(Breeze の既存ルートはそのまま)…
require __DIR__ . '/auth.php';

// ---- Gate デバッグ ----
Route::get('/debug/site-gate', function () {
    $u = auth()->user();
    return [
        'user_id'      => $u?->id,
        'role'         => $u?->role,
        'account_type' => $u?->account_type,
        'allowed'      => $u ? Gate::allows('site.build') : null,
        'as_user_3'    => Gate::forUser(User::find(3))->allows('site.build'),
    ];
})->middleware('auth');

// ---- 開発用: 即ログイン ----
if (app()->environment('local')) {
    Route::get('/dev/login/{id}', function (int $id) {
        $user = User::findOrFail($id);
        Auth::login($user);
        request()->session()->regenerate();
        return redirect('/debug/site-gate');
    })->whereNumber('id');

    Route::post('/dev/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    });
}
Route::middleware('auth')->group(function () {
    Route::get('/admin/site',            [SiteController::class, 'builder'])
        ->name('site.builder')
        ->middleware('site.entitled');   // ペイウォール用

    Route::get('/admin/site/pay',        [SiteController::class, 'paywall'])
        ->name('site.paywall');

    Route::post('/admin/site/checkout',  [SiteController::class, 'checkout'])
        ->name('site.checkout');

    Route::get('/admin/site/thanks',     [SiteController::class, 'thanks'])
        ->name('site.thanks');
});
// site.entitled を通すが、Controller は使わず即レス
Route::get('/admin/site/ping2', fn() => 'OK PING2')
    ->middleware(['auth', 'site.entitled']);
Route::get('/admin/site/ping1', fn() => 'OK PING1')->middleware('auth');

// auth すら無し
Route::get('/admin/site/ping0', fn() => 'OK PING0');
