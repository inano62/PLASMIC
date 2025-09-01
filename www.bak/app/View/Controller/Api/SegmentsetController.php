<?php

namespace App\View\Controller\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\InputData\Segmentset\SegmentsetSearchInputData;
use App\Application\InputData\Segmentset\SegmentsetUpdateInputData;
use App\Application\InputData\Segmentset\SegmentsetDeleteInputData;
use App\Application\InputData\Segmentset\SegmentsetSingleInputData;
use App\View\Controller\AbstractController;

class SegmentsetController extends AbstractController
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
        case 'update':
            return $this->update($request);
        case 'delete':
            return $this->delete($request);
        case 'single':
            return $this->single($request);
        default:
            return abort(404);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    private function search(Request $request): JsonResponse
    {
        $inputData = new SegmentsetSearchInputData($request->all());
        $outputData = $this->handleUseCase($inputData);
        return $outputData->renderJson();
    }

    private function update(Request $request)
    {
        $inputData = new SegmentsetUpdateInputData($request->all());
        $this->handleUseCase($inputData);
    }

    private function delete(Request $request)
    {
        $inputData = new SegmentsetDeleteInputData($request->all());
        $this->handleUseCase($inputData);
    }

    private function single(Request $request): JsonResponse
    {
        $inputData = new SegmentsetSingleInputData($request->all());
        $outputData = $this->handleUseCase($inputData);
        return $outputData->renderJson();
    }
}
