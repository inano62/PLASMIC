<?php

namespace App\Application\UseCase\Segmentset;

use App\Application\InputData\Segmentset\SegmentsetUpdateInputData;
use App\Domain\Writer\SegmentsetWriter;

class SegmentsetUpdateUseCase
{

    /**
     * @param SegmentsetWriter $segmentsetWriter
     */
    public function __construct(
        public readonly SegmentsetWriter $segmentsetWriter
    )
    {
    }

    /**
     * @param SegmentsetUpdateInputData $inputData
     * @return void
     */
    public function handle(SegmentsetUpdateInputData $inputData)
    {
        $segmentsetId = $inputData->segmentsetId;
        $name = $inputData->name;
        $segments = $inputData->segments;

        $this->segmentsetWriter->update($segmentsetId,$name,$segments);
    }
}
