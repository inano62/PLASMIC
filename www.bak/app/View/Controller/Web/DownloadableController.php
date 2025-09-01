<?php

namespace App\View\Controller\Web;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Application\InputData\Downloadable\DownloadableAttachmentInputData;

class DownloadableController extends AuthenticatedController
{
    /**
     * @param Request $request
     * @return View
     */
    public function get(Request $request)
    {
        $inputData = new DownloadableAttachmentInputData($request->all());
        $outputData = $this->handleUseCase($inputData);

        return $outputData->write();
    }
}
