<?php

namespace App\Adapter\Reader;

use App\Domain\Constant\ImportConstant;
use App\Domain\Object\Import\ImportForSearch;
use App\Domain\Reader\ImportReader;
use App\Infrastructure\Dao\ImportDao;


class ImportReaderImpl implements ImportReader
{
    /**
     * @param int $importId
     * @return bool
     */
    public function findIsUnprocessedByImportId(int $importId): bool
    {
        return !!ImportDao::where('id', $importId)->where('status', ImportConstant::STATUS_UNPROCESSED)->count();
    }

    /**
     * @param int $importId
     * @return ?string
     */
    public function findContentByImportId(int $importId): ?string
    {
        return ImportDao::where('id', $importId)->value('content');
    }


    public function findImportsForSearch(): ?array
    {
        return ImportDao::select('id', 'status', 'filename','createdAt')->get()->map(function($data) {
            return new ImportForSearch(
                $data->id,
                $data->status,
                $data->filename,
                strtotime($data->createdAt),
            );
        })->toArray();
    }

    public function findTypeByImportId(int $importId): ?int
    {
        return ImportDao::where('id', $importId)->value('type');
    }
}
