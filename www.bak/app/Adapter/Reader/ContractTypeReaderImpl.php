<?php

namespace App\Adapter\Reader;

use App\Domain\Reader\ContractTypeReader;
use App\Infrastructure\Dao\ContractTypeDao;

class ContractTypeReaderImpl implements ContractTypeReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToContractTypeIdBySearches(array $searches): array
    {
        $searchToContractTypeId = [];

        foreach (array_chunk($searches, 900) as $chunk) {

            foreach (ContractTypeDao::select('id', 'name')->whereIn('name', $chunk)->get() as $data) {
                $searchToContractTypeId[$data->name] = $data->id;
            }
        }

        if (in_array(NULL, $searches, TRUE)) {
            $contractTypeId = ContractTypeDao::where('name', '未登録')->value('id');
            if (!is_null($contractTypeId)) { $searchToContractTypeId[NULL] = $contractTypeId; }
        }

        return $searchToContractTypeId;
    }
}
