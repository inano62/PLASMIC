<?php

namespace App\Application\OutputData\Busho;

use App\Application\OutputData\AbstractOutputData;

class BushoSearchOutputData extends AbstractOutputData
{
    public function __construct(
        public readonly array $bumons,
        public readonly array $bushos,
    )
    {
    }
}
