<?php

namespace App\Application\InputData\Import;

class ImportExecSurveyInputData
{
    public function __construct(
        public readonly int $importId,
        public readonly int $time,
    )
    {
    }
}
