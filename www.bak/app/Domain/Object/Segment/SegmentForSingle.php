<?php

namespace App\Domain\Object\Segment;

class SegmentForSingle
{
    /**
     * @param int $type
     * @param string $color
     * @param float|null $value01
     */
    public function __construct(
        public readonly int $type,
        public readonly string $color,
        public readonly ?float $value01,
    )
    {
    }
}
