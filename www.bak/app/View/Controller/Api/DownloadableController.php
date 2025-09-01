<?php

namespace App\View\Controller\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Application\InputData\Downloadable\DownloadableSearchInputData;

class DownloadableController extends AuthenticatedController
{
    /**
     * @param Request $request
     * @return void
     */
    public function post(Request $request)
    {
        switch ($request->input('action')) {

        case 'search':
            return $this->search($request);

        default:
            return abort(404);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function search(Request $request)
    {
        $inputData = new DownloadableSearchInputData();
        $outputData = $this->handleUseCase($inputData);
        return $outputData->renderJson();
    }
}
