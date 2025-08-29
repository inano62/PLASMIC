<?php

namespace App\View\Controller\Api;

use Illuminate\Http\Request;
use App\View\Controller\AbstractController;

class LogoutController extends AbstractController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        $request->session()->flush();
    }
}
