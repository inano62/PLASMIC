<?php

namespace App\Application\Service\Csv;

interface CsvTime3xService
{
    public function readForImport(string $content): ?array;
}
