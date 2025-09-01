<?php

namespace App\Application\UseCase\Import;

use App\Application\InputData\Import\ImportExecSurveyInputData;
use App\Application\Service\Excel\ExcelSurveyService;
use App\Domain\Reader\ImportReader;
use App\Domain\Writer\SurveyCampaignWriter;
use App\Domain\Reader\SurveyCampaignReader;


class ImportExecSurveyUseCase
{
    /**
     * @return void
     */
    public function __construct(
        public readonly ExcelSurveyService $excelSurveyService,
        public readonly ImportReader $importReader,
        public readonly SurveyCampaignWriter $surveyCampaignWriter,
        public readonly SurveyCampaignReader $surveyCampaignReader,
    )
    {
    }

    /**
     * @param ImportExecSurveyInputData $inputData
     * @return void
     */
    public function handle(ImportExecSurveyInputData $inputData)
    {
        $importId = $inputData->importId;

        $content = $this->importReader->findContentByImportId($importId);

        $campaign = $this->excelSurveyService->readForImport($content);
        if (is_null($campaign)) {
            $this->importWriter->updateFailedByImportId($importId);
            abort(404);
        }

        $surveyCampaignId = $this->surveyCampaignReader->findSurveyCampaignIdByPeriod($campaign->timeFrom, $campaign->timeTo);
        if(is_null($surveyCampaignId)){ abort(404); }

        $this->surveyCampaignWriter->import($campaign,$surveyCampaignId);
    }
}
