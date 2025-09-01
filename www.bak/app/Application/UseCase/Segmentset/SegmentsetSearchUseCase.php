<?php

namespace App\Application\UseCase\Segmentset;

use App\Application\InputData\Segmentset\SegmentsetSearchInputData;
use App\Application\OutputData\Segmentset\SegmentsetSearchOutputData;
use App\Domain\Reader\SegmentsetReader;

class SegmentsetSearchUseCase
{
    /**
     * @param SegmentsetReader $segmentsetReader
     */
    public function __construct(
        public readonly SegmentsetReader $segmentsetReader
    )
    {
    }

    /**
     * @param SegmentsetSearchInputData $inputData
     * @return SegmentsetSearchOutputData
     */
    public function handle(SegmentsetSearchInputData $inputData): SegmentsetSearchOutputData
    {
        $segmentsets = $this->segmentsetReader->findSegmentsetsForSearch();

        return new SegmentsetSearchOutputData(
            $segmentsets,
        );
    }
}
