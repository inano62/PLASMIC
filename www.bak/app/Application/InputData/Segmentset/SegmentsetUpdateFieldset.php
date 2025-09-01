<?php

namespace App\Application\InputData\Segmentset;

class SegmentsetUpdateFieldset
{

    /**
     * @param int $type
     * @param string $color
     * @param float|null $value01
     */
    public function __construct(
        public readonly int $type,
        public readonly string $color,
        public readonly ?float $value01
    )
    {
    }
}
