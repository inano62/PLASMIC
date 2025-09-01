<?php

namespace App\Domain\Reader;

interface SegmentReader
{
    /**
     * @param int $segmentsetId
     * @return array
     */
    public function findSegmentsForSingle(int $segmentsetId): array;
}
