<?php

namespace App\Adapter\Reader;

use App\Domain\Reader\RoleReader;
use App\Infrastructure\Dao\RoleDao;

class RoleReaderImpl implements RoleReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToRoleIdBySearches(array $searches): array
    {
        $searchToRoleId = [];

        foreach (array_chunk($searches, 900) as $chunk) {

            foreach (RoleDao::select('id', 'name')->whereIn('name', $chunk)->get() as $data) {
                $searchToRoleId[$data->name] = $data->id;
            }
        }

        if (in_array(NULL, $searches, TRUE)) {
            $roleId = RoleDao::where('name', '未登録')->value('id');
            if (!is_null($roleId)) { $searchToRoleId[NULL] = $roleId; }
        }

        return $searchToRoleId;
    }
}
