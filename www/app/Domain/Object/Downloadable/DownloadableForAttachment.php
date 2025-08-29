<?php

namespace App\Domain\Object\Downloadable;

class DownloadableForAttachment
{
    /**
     * @param int $downloadableId
     * @param string $name
     * @param string $mimeType
     * @param string $content
     * @return void
     */
    public function __construct(
        int $downloadableId,
        string $name,
        string $mimeType,
        string $content
    )
    {
        $this->downloadableId = $downloadableId;
        $this->name = $name;
        $this->mimeType = $mimeType;
        $this->content = $content;
    }
}
