<?php

use Tests\AbstractTestCase;

use App\Infrastructure\Dao\SegmentsetDao;
use App\Infrastructure\Dao\SegmentDao;
use App\Application\InputData\Segmentset\SegmentsetSearchInputData;
use App\Application\InputData\Segmentset\SegmentsetUpdateInputData;
use App\Application\InputData\Segmentset\SegmentsetDeleteInputData;
use App\Application\InputData\Segmentset\SegmentsetSingleInputData;

class SegmentsetTest extends AbstractTestCase
{
    /**
     * @return void
     */

    const SEGMENTSET_ID = 1;
    const NAME = '男女比較';

    public function testSegmentset()
    {
        SegmentsetDao::create([
            'name' => self::NAME,
            'order' => 1
        ]);
        SegmentDao::create([
            'segmentsetId' => self::SEGMENTSET_ID,
            'type' => 1,
            'color' => 15,
            'order' => 1,
        ]);
        $inputData = new SegmentsetSearchInputData();
        $outputData = $this->handleUseCase($inputData);
        $this->assertCount(1,$outputData->segmentsets);


        $inputData = new SegmentsetUpdateInputData([
            'name' => '労働時間比較',
            'segments' => [
                [
                    'type' => 2,
                    'color' => '#ffffff',
                ],
            ]
        ]);
        $outputData = $this->handleUseCase($inputData);
        $this->assertSame(2,SegmentsetDao::count());
        $this->assertSame(2,SegmentDao::count());
        $segmentsetId =  SegmentsetDao::orderBy('id','desc')->value('id');

        $inputData = new SegmentsetUpdateInputData([
            'segmentsetId' => $segmentsetId,
            'name' => '部署比較',
            'segments' => [
                [
                    'type' => 3,
                    'color' => '#ffffff',
                    'value01' => 2022,
                ],
                [
                    'type' => 4,
                    'color' => '#2f8a3b',
                    'value01' => 2023,
                ],
            ]
        ]);
        $outputData = $this->handleUseCase($inputData);
        $this->assertSame(2,SegmentsetDao::count());
        $this->assertSame(3,SegmentDao::count());


        $inputData = new SegmentsetDeleteInputData([
            'segmentsetId' => $segmentsetId,
        ]);
        $outputData = $this->handleUseCase($inputData);
        $this->assertSame(1,SegmentsetDao::count());
        $this->assertSame(1,SegmentDao::count());


        $inputData = new SegmentsetSingleInputData([
            'segmentsetId' =>  self::SEGMENTSET_ID,
        ]);
        $outputData = $this->handleUseCase($inputData);
        $this->assertSame(self::SEGMENTSET_ID,$outputData->segmentsetId);
        $this->assertSame(self::NAME,$outputData->name);
        $this->assertCount(1,$outputData->segments);
    }
}
