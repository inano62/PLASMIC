<?php

namespace App\Adapter\Reader;

use App\Domain\Object\BumonGroup\BumonGroupForSearch;
use App\Domain\Reader\BumonGroupReader;
use App\Infrastructure\Dao\BumonGroupDao;

class BumonGroupReaderImpl implements BumonGroupReader
{
    /**
     * @return \App\Domain\Object\BumonGroup\BumonGroupForSearch[]
     */
    public function findBumonGroupsForSearch(): array
    {
        return BumonGroupDao::select('id', 'name')->get()->map(function($data) {
            return new BumonGroupForSearch(
                $data->id,
                $data->name,
            );
        })->toArray();
    }
}
