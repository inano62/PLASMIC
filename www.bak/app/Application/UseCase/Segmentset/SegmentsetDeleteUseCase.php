<?php

namespace App\Application\UseCase\Segmentset;

use App\Application\InputData\Segmentset\SegmentsetDeleteInputData;
use App\Domain\Writer\SegmentsetWriter;

class SegmentsetDeleteUseCase
{
    /**
     * @return void
     */
    public function __construct(
        public readonly SegmentsetWriter $segmentsetWriter
    )
    {
    }

    /**
     * @param SegmentsetDeleteInputData $inputData
     * @return void
     */
    public function handle(SegmentsetDeleteInputData $inputData)
    {
        $segmentsetId = $inputData->segmentsetId;

        $this->segmentsetWriter->delete($segmentsetId);
    }
}
