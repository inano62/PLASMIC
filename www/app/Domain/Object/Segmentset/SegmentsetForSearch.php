<?php

namespace App\Domain\Object\Segmentset;

class SegmentsetForSearch
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
