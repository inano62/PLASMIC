<?php

namespace App\Adapter\Reader;

use App\Domain\Reader\JoinWayReader;
use App\Infrastructure\Dao\JoinWayDao;

class JoinWayReaderImpl implements JoinWayReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToJoinWayIdBySearches(array $searches): array
    {
        $searchToJoinWayId = [];

        foreach (array_chunk($searches, 900) as $chunk) {

            foreach (JoinWayDao::select('id', 'name')->whereIn('name', $chunk)->get() as $data) {
                $searchToJoinWayId[$data->name] = $data->id;
            }
        }

        if (in_array(NULL, $searches, TRUE)) {
            $joinWayId = JoinWayDao::where('name', '未登録')->value('id');
            if (!is_null($joinWayId)) { $searchToJoinWayId[NULL] = $joinWayId; }
        }

        return $searchToJoinWayId;
    }
}
