<?php

namespace App\Domain\Reader;

interface RoleReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToRoleIdBySearches(array $searches): array;
}
