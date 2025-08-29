<?php

namespace App\Domain\Reader;

interface JoinWayReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToJoinWayIdBySearches(array $searches): array;
}
