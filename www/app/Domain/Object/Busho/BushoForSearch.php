<?php

namespace App\Domain\Object\Busho;

class BushoForSearch
{
    /**
     * @param int $bushoId
     * @param string $name
     * @return void
     */
    public function __construct(
        public readonly int $bushoId,
        public readonly string $name,
    )
    {
    }
}
