<?php

namespace App\Adapter\Reader;

use App\Domain\Reader\PositionReader;
use App\Infrastructure\Dao\PositionDao;

class PositionReaderImpl implements PositionReader
{
    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToPositionIdBySearches(array $searches): array
    {
        $searchToPositionId = [];

        foreach (array_chunk($searches, 900) as $chunk) {

            foreach (PositionDao::select('id', 'name')->whereIn('name', $chunk)->get() as $data) {
                $searchToPositionId[$data->name] = $data->id;
            }
        }

        if (in_array(NULL, $searches, TRUE)) {
            $positionId = PositionDao::where('name', '未登録')->value('id');
            if (!is_null($positionId)) { $searchToPositionId[NULL] = $positionId; }
        }

        return $searchToPositionId;
    }
}
