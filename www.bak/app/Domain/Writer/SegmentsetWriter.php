<?php

namespace App\Domain\Writer;

interface SegmentsetWriter
{
    /**
     * @param int|null $segmentsetId
     * @param string $name
     * @param array $segments
     * @return void
     */
    public function update(?int $segmentsetId, string $name, array $segments);

    /**
     * @param int $segmentsetId
     * @return void
     */
    public function delete(int $segmentsetId);
}
