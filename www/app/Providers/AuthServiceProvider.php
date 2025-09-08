<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // モデルポリシーがあればここに。
    ];

    public function boot(): void
    {
        Gate::define('site.build', function (User $user) {
            if ($user->role === 'admin') {
                return true;
            }
            if ($user->role === 'lawyer' && $user->account_type === 'pro') {
                return true;
            }
            return false;
        });
    }
}
