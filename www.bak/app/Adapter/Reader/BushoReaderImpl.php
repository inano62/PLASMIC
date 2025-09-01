<?php

namespace App\Adapter\Reader;

use App\Domain\Object\Busho\BushoForSearch;
use App\Domain\Reader\BushoReader;
use App\Infrastructure\Dao\BushoDao;

class BushoReaderImpl implements BushoReader
{

    public function findBushosForSearch(): array
    {
        return BushoDao::select('id', 'name')->get()->map(function($data) {
            return new BushoForSearch(
                $data->id,
                $data->name,
            );
        })->toArray();
    }

    public function findBushosForSearchByBushoIds(array $bushoIds): array
    {
        $bushos = [];
        foreach (array_chunk($bushoIds, 900) as $chunk) {
            foreach (BushoDao::select('id', 'name')->whereIn('id', $chunk)->get() as $data) {
                $bushos[] = new BushoForSearch(
                    $data->id,
                    $data->name,
                );
            }
        }

        return $bushos;
    }
}
