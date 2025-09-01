<?php

namespace App\Domain\Reader;

interface BushoReader
{
    public function findBushosForSearch(): array;

    public function findBushosForSearchByBushoIds(array $bushoIds): array;
}
