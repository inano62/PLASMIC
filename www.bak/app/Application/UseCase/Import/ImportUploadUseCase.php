<?php

namespace App\Application\UseCase\Import;

use App\Application\InputData\Import\ImportUploadInputData;
use App\Domain\Object\Import\ImportForCreate;
use App\Domain\Writer\ImportWriter;

class ImportUploadUseCase
{
    /**
     * @return void
     */
    public function __construct(
        public readonly ImportWriter $importWriter
    )
    {
    }

    /**
     * @param ImportUploadInputData $inputData
     * @return void
     */
    public function handle(ImportUploadInputData $inputData)
    {
        $file = $inputData->file;
        $type = $inputData->type;

        $content = $file->get();
        $filename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        $import = new ImportForCreate(
            $filename,
            $mimeType,
            $content,
            $type,
        );

        $this->importWriter->create($import);
    }
}
