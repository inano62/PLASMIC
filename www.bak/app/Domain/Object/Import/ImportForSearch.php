<?php

namespace App\Domain\Object\Import;

class ImportForSearch
{
    /**
     * @param int $importId
     * @param int $status
     * @param string $filename
     * @param int $createdTime
     * @return void
     */
    public function __construct(
        public readonly int $importId,
        public readonly int $status,
        public readonly string $filename,
        public readonly int $createdTime,
    )
    {
    }
}
