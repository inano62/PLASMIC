<?php

namespace App\View\Controller\Api;

use Illuminate\Http\Request;
use App\Application\InputData\Import\ImportExecInputData;
use App\Application\InputData\Import\ImportSearchInputData;
use App\Application\InputData\Import\ImportUploadInputData;
use App\View\Controller\AbstractController;

class ImportController extends AbstractController
{
    /**
     * @param Request $request
     * @return void
     */
    public function post(Request $request)
    {
        switch ($request->input('action')) {

        case 'exec':
            return $this->exec($request);

        case 'search':
            return $this->search($request);

        case 'upload':
            return $this->upload($request);

        default:
            return abort(404);
        }
    }

    /**
     * @access private
     * @param Request $request
     * @return void
     */
    private function exec(Request $request)
    {
        ini_set("memory_limit", "512M");

        $inputData = new ImportExecInputData($request->all(), time());
        $this->closeConnection();

        $this->handleUseCase($inputData);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function search(Request $request)
    {
        $inputData = new ImportSearchInputData($request->all());
        $outputData = $this->handleUseCase($inputData);
        return $outputData->renderJson();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    private function upload(Request $request)
    {
        $inputData = new ImportUploadInputData($request->all());
        $this->handleUseCase($inputData);
    }
}
