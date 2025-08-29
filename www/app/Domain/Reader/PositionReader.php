<?php

namespace App\Domain\Reader;

interface PositionReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToPositionIdBySearches(array $searches): array;
}
