<?php

namespace App\Domain\Writer;

use App\Domain\Object\Import\ImportForCreate;

interface ImportWriter
{
    /**
     * @param ImportForCreate $import
     * @return void
     */
    public function create(ImportForCreate $import): int;

    /**
     * @param int $importId
     * @return bool
     */
    public function updateProcessingByImportId(int $importId);

    /**
     * @param int $importId
     * @return void
     */
    public function updateSucceededByImportId(int $importId);

    /**
     * @param int $importId
     * @return void
     */
    public function updateFailedByImportId(int $importId);
}
