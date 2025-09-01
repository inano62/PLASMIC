<?php

namespace App\View\Controller\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Application\InputData\Microsoft\MicrosoftCallbackInputData;
use App\View\Controller\AbstractController;

class MicrosoftAuthenticateController extends AbstractController
{
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function get(Request $request)
    {
        return $this->callback($request);
    }

    /**
     * @access private
     * @param Request $request
     * @return void
     */
    private function callback(Request $request)
    {
        $inputData = new MicrosoftCallbackInputData($request->all());

        try {
            $outputData = $this->handleUseCase($inputData);
            $outputData->writeSession();
            return redirect('/');
        } catch (\Exception $e) {
            return view('microsoft.failed');
        }
    }
}
