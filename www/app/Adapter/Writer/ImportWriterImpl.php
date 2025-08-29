<?php

namespace App\Adapter\Writer;

use App\Domain\Writer\ImportWriter;
use App\Domain\Constant\ImportConstant;
use App\Domain\Object\Import\ImportForCreate;
use App\Infrastructure\Dao\ImportDao;

class ImportWriterImpl implements ImportWriter
{
    /**
     * @param ImportForCreate $import
     * @return void
     */
    public function create(ImportForCreate $import): int
    {
        return ImportDao::create([
            'filename' => $import->filename,
            'mimeType' => $import->mimeType,
            'content' => $import->content,
            'type' => $import->type,
        ])->id;
    }

    /**
     * @param int $importId
     * @return bool
     */
    public function updateProcessingByImportId(int $importId)
    {
        ImportDao::where('id', $importId)->update([
            'status' => ImportConstant::STATUS_PROCESSING,
        ]);
    }

    /**
     * @param int $importId
     * @return void
     */
    public function updateSucceededByImportId(int $importId)
    {
        ImportDao::where('id', $importId)->update([
            'status' => ImportConstant::STATUS_SUCCEEDED,
        ]);
    }

    /**
     * @param int $importId
     * @return void
     */
    public function updateFailedByImportId(int $importId)
    {
        ImportDao::where('id', $importId)->update([
            'status' => ImportConstant::STATUS_FAILED,
        ]);
    }
}
