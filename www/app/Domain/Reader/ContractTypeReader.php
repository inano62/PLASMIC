<?php

namespace App\Domain\Reader;

interface ContractTypeReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToContractTypeIdBySearches(array $searches): array;
}
