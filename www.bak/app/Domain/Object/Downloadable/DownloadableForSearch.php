<?php

namespace App\Domain\Object\Downloadable;

class DownloadableForSearch
{
    /**
     * @param int $downloadableId
     * @param string $name
     * @param int $createdTime
     * @return void
     */
    public function __construct(
        int $downloadableId,
        string $name,
        int $createdTime
    )
    {
        $this->downloadableId = $downloadableId;
        $this->name = $name;
        $this->createdTime = $createdTime;
    }
}
