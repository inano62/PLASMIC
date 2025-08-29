<?php

namespace App\Application\UseCase\Import;

use App\Application\InputData\Import\ImportExecInputData;
use App\Application\InputData\Import\ImportExecSurveyInputData;
use App\Application\InputData\Import\ImportExecUserInputData;
use App\Application\UseCase\Import\ImportExecUserUseCase;
use App\Application\UseCase\Import\ImportExecSurveyUseCase;
use App\Domain\Reader\ImportReader;
use App\Domain\Writer\ImportWriter;

class ImportExecUseCase
{
    /**
     * @return void
     */
    public function __construct(
        public readonly ImportExecUserUseCase $importExecUserUseCase,
        public readonly ImportExecSurveyUseCase $importExecSurveyUseCase,
        public readonly ImportReader $importReader,
        public readonly ImportWriter $importWriter,
    )
    {
    }

    /**
     * @param ImportExecInputData $inputData
     * @return void
     */
    public function handle(ImportExecInputData $inputData)
    {
        $time = $inputData->time;
        $importId = $inputData->importId;

        $type = $this->importReader->findTypeByImportId($importId);

        if (!$this->importReader->findIsUnprocessedByImportId($importId)) { return abort(404); }
        $this->importWriter->updateProcessingByImportId($importId);

        switch ($type) {
            case 1:
                try {
                    $this->importExecUserUseCase->handle(new ImportExecUserInputData($importId, $time));
                    $this->importWriter->updateSucceededByImportId($importId);
                } catch (\Throwable $_) {
                    $this->importWriter->updateFailedByImportId($importId);
                    throw $_;
                }
                break;

            case 2:
                //try {
                    $this->importExecSurveyUseCase->handle(new ImportExecSurveyInputData($importId, $time));
                    $this->importWriter->updateSucceededByImportId($importId);
                //} catch (\Throwable $_) {
                //    $this->importWriter->updateFailedByImportId($importId);
                //    throw $_;
                //}
                break;

            default:
                $this->importWriter->updateFailedByImportId($importId);
                abort(404);
        }
    }
}
