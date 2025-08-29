<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use App\Application\InputData\Import\ImportUploadInputData;
use App\Application\InputData\Import\ImportSearchInputData;
use App\Application\InputData\Import\ImportExecInputData;
use App\Infrastructure\Dao\ImportDao;
use App\Infrastructure\Dao\SurveyAnswerDao;
use App\Infrastructure\Dao\SurveyCampaignDao;
use App\Infrastructure\Dao\UserDao;

class ImportSurveyTest extends AbstractTestCase
{
    const USER_ID = 1;

    /**
     * @return void
     */
    public function testExec()
    {
        ini_set('memory_limit', '1G');

        $time = time();

        $surveyCampaignId = SurveyCampaignDao::create([
            'name' => 'キャンペーン',
            'dateFrom' => '2022-01-01',
            'dateTo' => '2022-12-31',
        ])->id;

        UserDao::create([
            'employeeCode' => 1038374,
        ]);

        $this->assertSame(0, ImportDao::count());
        $file = $this->getUploadedFile('Survey-sample.xlsx');
        $inputData = new ImportUploadInputData([
            'file' => $file,
            'type' => 2,
        ]);
        $this->handleUseCase($inputData);
        $this->assertSame(1,ImportDao::count());

        $inputData = new ImportSearchInputData();
        $outputData = $this->handleUseCase($inputData);
        $this->assertCount(1, $outputData->imports);

        $importId = $outputData->imports[0]->importId;
        $inputData = new ImportExecInputData([
            'importId' => $importId,
        ], $time);
        $outputData = $this->handleUseCase($inputData);

        $this->assertNotSame(0, SurveyAnswerDao::where('surveyCampaignId', $surveyCampaignId)->count());
    }
}
