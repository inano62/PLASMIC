<?php

namespace App\Application\OutputData\Segmentset;

use App\Application\OutputData\AbstractOutputData;

class SegmentsetSingleOutputData extends AbstractOutputData
{
    /**
     * @param int $segmentsetId
     * @param string $name
     * @param array $segments
     */
    public function __construct(
        public readonly int $segmentsetId,
        public readonly string $name,
        public readonly array $segments,
    )
    {
    }
}
