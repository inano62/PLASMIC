<?php

namespace App\Domain\Reader;

interface BumonReader
{
    public function findBumonsForSearch(): array;
}
