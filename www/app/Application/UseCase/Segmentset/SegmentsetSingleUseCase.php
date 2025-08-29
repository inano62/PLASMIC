<?php

namespace App\Application\UseCase\Segmentset;

use App\Application\InputData\Segmentset\SegmentsetSingleInputData;
use App\Application\OutputData\Segmentset\SegmentsetSingleOutputData;
use App\Domain\Reader\SegmentsetReader;
use App\Domain\Reader\SegmentReader;

class SegmentsetSingleUseCase
{

    /**
     * @param SegmentsetReader $segmentsetReader
     * @param SegmentReader $segmentReader
     */
    public function __construct(
        public readonly SegmentsetReader $segmentsetReader,
        public readonly SegmentReader $segmentReader
    )
    {
    }

    /**
     * @param SegmentsetSingleInputData $inputData
     * @return SegmentsetSingleOutputData
     */
    public function handle(SegmentsetSingleInputData $inputData): SegmentsetSingleOutputData
    {
        $segmentsetId = $inputData->segmentsetId;

        $segmentset = $this->segmentsetReader->findSegmentsetForSingle($segmentsetId);
        if(is_null($segmentset)) { abort(404); }

        $segments = $this->segmentReader->findSegmentsForSingle($segmentsetId);
        if(empty($segments)) { abort(404); }

        return new SegmentsetSingleOutputData(
            $segmentset->segmentsetId,
            $segmentset->name,
            $segments
        );
    }
}
