<?php

namespace App\View\Controller\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Application\InputData\Api\ApiAuthorizeInputData;
use App\Domain\Constant\AuthConstant;
use App\Domain\Object\User\Session;
use App\View\Controller\AbstractController;

abstract class AuthenticatedController extends AbstractController
{
    /**
     * @var string|null
     */
    protected $authEmail = NULL;

    /**
     * @param string|string[] $requiredPermission
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function (Request $request, Callable $next) {

            $authEmail = $request->session()->get(AuthConstant::SESSION_EMAIL_KEY);

            if (env('APP_DEBUG') && (
                Str::startsWith($request->server('HTTP_HOST'), 'localhost:') ||
                $request->server('HTTP_HOST') === 'admin-lms.aza.jp'
            )) { $authEmail = 'develop@example.com'; }

            if (is_null($authEmail)) { return $request->ajax() ? \Response::json(['code' => 403], 403) : redirect('/'); }

            $this->authEmail = $authEmail;

            return $next($request);
        });
    }

    /**
     * @access protected
     * @return string
     */
    protected function getAuthEmail(): string
    {
        return $this->authEmail;
    }
}
