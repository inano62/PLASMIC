<?php

namespace App\Application\OutputData\Downloadable;

use App\Application\OutputData\AbstractOutputData;

class DownloadableSearchOutputData extends AbstractOutputData
{
    /**
     * @param \App\Domain\Object\Downloadable\DownloadableForSearch $downloadables[]
     * @return void
     */
    public function __construct(
        array $downloadables
    )
    {
        $this->downloadables = $downloadables;
    }
}
