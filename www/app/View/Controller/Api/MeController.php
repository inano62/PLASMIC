<?php

namespace App\View\Controller\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\InputData\Me\MeInputData;

class MeController extends AuthenticatedController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        return response()->json([
            'email' => $this->getAuthEmail(),
        ]);
    }
}
