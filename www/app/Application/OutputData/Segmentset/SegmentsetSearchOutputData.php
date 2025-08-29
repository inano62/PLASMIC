<?php

namespace App\Application\OutputData\Segmentset;

use App\Application\OutputData\AbstractOutputData;

class SegmentsetSearchOutputData extends AbstractOutputData
{
    /**
     * @param array $segementsets
     */
    public function __construct(
        public readonly array $segmentsets,
    )
    {
    }
}
