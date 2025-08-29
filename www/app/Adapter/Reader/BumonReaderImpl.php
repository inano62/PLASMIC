<?php

namespace App\Adapter\Reader;

use App\Domain\Object\Bumon\BumonForSearch;
use App\Domain\Reader\BumonReader;
use App\Infrastructure\Dao\BumonDao;

class BumonReaderImpl implements BumonReader
{
    /**
     * @return \App\Domain\Object\Bumon\BumonForSearch[]
     */
    public function findBumonsForSearch(): array
    {
        return BumonDao::select('id', 'name')->get()->map(function($data) {
            return new BumonForSearch(
                $data->id,
                $data->name,
            );
        })->toArray();
    }
}
