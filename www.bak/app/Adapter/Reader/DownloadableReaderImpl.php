<?php

namespace App\Adapter\Reader;

use App\Domain\Reader\DownloadableReader;
use App\Infrastructure\Dao\DownloadableDao;
use App\Domain\Object\Downloadable\DownloadableForAttachment;
use App\Domain\Object\Downloadable\DownloadableForSearch;

class DownloadableReaderImpl implements DownloadableReader
{
    /**
     * @return \App\Domain\Object\Downloadable\DownloadableForSearch[]
     */
    public function findDownloadablesForSearch(): array
    {
        return DownloadableDao::select('id', 'name', 'createdAt')->orderBy('id', 'DESC')->get()->map(function($data) {
            return new DownloadableForSearch(
                $data->id,
                $data->name,
                strtotime($data->createdAt),
            );
        })->toArray();
    }

    /**
     * @param int $downloadableId
     * @return ?DownloadableForAttachment
     */
    public function findDownloadableForAttachmentByDownloadableId(int $downloadableId): ?DownloadableForAttachment
    {
        $data = DownloadableDao::select('id', 'name', 'mimeType', 'content')->where('id', $downloadableId)->first();
        if (is_null($data)) { return NULL; }

        return new DownloadableForAttachment(
            $data->id,
            $data->name,
            $data->mimeType,
            $data->content
        );
    }
}
