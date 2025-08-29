<?php

namespace App\Application\OutputData\Downloadable;

use App\Domain\Object\Downloadable\DownloadableForAttachment;
use App\Application\OutputData\AbstractOutputData;

class DownloadableAttachmentOutputData extends AbstractOutputData
{
    /**
     * @param DownloadableForAttachment $downloadable
     * @return void
     */
    public function __construct(
        DownloadableForAttachment $downloadable
    )
    {
        $this->downloadable = $downloadable;
    }

    /**
     * @return void
     */
    public function write()
    {
        $downloadable = $this->downloadable;

        header('Content-Disposition: attachment; filename="' . $downloadable->name . '"');
        header('Content-Type: ' . $downloadable->mimeType);
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.strlen($downloadable->content));
        print $downloadable->content;

        exit;
    }
}
