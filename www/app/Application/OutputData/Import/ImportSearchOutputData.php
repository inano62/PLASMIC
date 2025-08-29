<?php

namespace App\Application\OutputData\Import;

use App\Application\OutputData\AbstractOutputData;

class ImportSearchOutputData extends AbstractOutputData
{
    /**
     * @param \App\Domain\Object\Import\ImportForSearch[] $userImports
     * @return void
     */
    public function __construct(
        public readonly array $imports,
    )
    {
    }
}
