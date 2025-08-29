<?php

namespace App\Domain\Reader;

use App\Domain\Object\Downloadable\DownloadableForAttachment;

interface DownloadableReader
{
    /**
     * @return \App\Domain\Object\Downloadable\DownloadableForSearch[]
     */
    public function findDownloadablesForSearch(): array;

    /**
     * @param int $downloadableId
     * @return ?DownloadableForAttachment
     */
    public function findDownloadableForAttachmentByDownloadableId(int $downloadableId): ?DownloadableForAttachment;
}
