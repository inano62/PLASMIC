<?php

namespace App\Domain\Object\Segmentset;

class SegmentsetForSingle
{
    /**
     * @param int $segmentsetId
     * @param string $name
     */
    public function __construct(
        public readonly int $segmentsetId,
        public readonly string $name,
    )
    {
    }
}
