<?php

namespace App\Infrastructure\Middleware;

use Closure;
use App\Domain\Constant\CurriculumApplicationConstant;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        // ゼント
        // $ipAddresses = ['39.110.207.144', '114.141.126.57', '114.141.124.22', '114.141.126.56'];
        // $isUs = in_array(request()->ip(), $ipAddresses, TRUE);
        // $isUs = $isUs || in_array(request()->server('HTTP_X_FORWARDED_FOR'), $ipAddresses, TRUE);
        // $isUs = $isUs || in_array(request()->server('HTTP_HOST'), ['admin.hdy.online', 'hdy.online', 'localhost:8000', 'admin-stg.hdy.online'], TRUE);

        // if (!$isUs) { return response()->view('user.maintenance'); }

        return parent::handle($request, $next);
    }
}
