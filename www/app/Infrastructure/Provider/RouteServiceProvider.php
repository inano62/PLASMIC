<?php

namespace App\Infrastructure\Provider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $server = request()->server('HTTP_HOST');
        if (!Str::startsWith($server, '.hdy.online')) {
            \URL::forceScheme('https');
            $this->configure();
        } else if (env('APP_DEBUG') && Str::startsWith($server, 'localhost:')) {
            $this->configure();
        }
    }

    /**
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('extapi', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }

    /**
     * @return void
     */
    protected function configure()
    {
        if (request()->ajax()) {
            Route::prefix('api')
                ->middleware('webapi')
                ->namespace('App\View\Controller\Api')
                ->group(base_path('routes/api.php'));
        } else {
            Route::middleware('web')
                ->namespace('App\View\Controller\Web')
                ->group(base_path('routes/web.php'));
        }
    }
}
