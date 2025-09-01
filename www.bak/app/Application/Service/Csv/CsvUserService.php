<?php

namespace App\Application\Service\Csv;

interface CsvUserService
{
    /**
     * @param string $content
     * @return [string[][], \App\Domain\Object\User\UserImportColumnGroup[]]
     */
    public function readForImport(string $content): array;
}
