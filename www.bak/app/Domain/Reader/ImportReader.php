<?php

namespace App\Domain\Reader;

interface ImportReader
{
    /**
     * @param int $importId
     * @return bool
     */
    public function findIsUnprocessedByImportId(int $importId): bool;

    /**
     * @param int $importId
     * @return ?string
     */
    public function findContentByImportId(int $importId): ?string;


    public function findImportsForSearch(): ?array;


    public function findTypeByImportId(int $importId): ?int;
}
