<?php

namespace App\Application\UseCase\Import;

use App\Application\InputData\Import\ImportSearchInputData;
use App\Application\OutputData\Import\ImportSearchOutputData;
use App\Domain\Reader\ImportReader;

class ImportSearchUseCase
{
    /**
     * @return void
     */
    public function __construct(
        public readonly ImportReader $importReader
    )
    {
    }

    /**
     * @param ImportSearchInputData $inputData
     * @return ImportSearchOutputData
     */
    public function handle(ImportSearchInputData $inputData): ImportSearchOutputData
    {
        $imports = $this->importReader->findImportsForSearch();

        return new ImportSearchOutputData(
            $imports,
        );
    }
}
