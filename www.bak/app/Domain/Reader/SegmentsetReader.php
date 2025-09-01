<?php

namespace App\Domain\Reader;
use App\Domain\Object\Segmentset\SegmentsetForSingle;

interface SegmentsetReader
{

    /**
     * @return array
     */
    public function findSegmentsetsForSearch(): array;

    /**
     * @param int $segmentsetId
     * @return SegmentsetForSingle|null
     */
    public function findSegmentsetForSingle(int $segmentsetId): ?SegmentsetForSingle;
}
