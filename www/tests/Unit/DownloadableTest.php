<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use App\Application\InputData\Downloadable\DownloadableSearchInputData;
use App\Application\InputData\Downloadable\DownloadableAttachmentInputData;
use App\Infrastructure\Dao\DownloadableDao;

class DownloadableTest extends AbstractTestCase
{
    const USER_ID = 1;

    /**
     * @return void
     */
    public function testUser()
    {
        $time = time();

        $downloadableId = DownloadableDao::create([
            'name' => 'test.txt',
            'mimeType' => '	text/plain',
            'content' => '',
        ])->id;

        $inputData = new DownloadableSearchInputData();
        $outputData = $this->handleUseCase($inputData);

        $this->assertCount(1, $outputData->downloadables);

        $inputData = new DownloadableAttachmentInputData(['id' => $downloadableId]);
        $outputData = $this->handleUseCase($inputData);
    }
}
