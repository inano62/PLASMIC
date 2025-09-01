<?php

namespace App\Application\InputData\Import;

class ImportExecUserInputData
{
    public function __construct(
        public readonly int $importId,
        public readonly int $time,
    )
    {
    }
}
