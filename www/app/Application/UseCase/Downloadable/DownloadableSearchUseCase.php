<?php

namespace App\Application\UseCase\Downloadable;

use App\Application\InputData\Downloadable\DownloadableSearchInputData;
use App\Application\OutputData\Downloadable\DownloadableSearchOutputData;
use App\Domain\Reader\DownloadableReader;

class DownloadableSearchUseCase
{
    /**
     * @param DownloadableReader $downloadableReader
     * @return void
     */
    public function __construct(
        DownloadableReader $downloadableReader
    )
    {
        $this->downloadableReader = $downloadableReader;
    }

    /**
     * @param DownloadableSearchInputData $inputData
     * @return DownloadableSearchOutputData
     */
    public function handle(DownloadableSearchInputData $inputData): DownloadableSearchOutputData
    {
        $downloadables = $this->downloadableReader->findDownloadablesForSearch();

        return new DownloadableSearchOutputData(
            $downloadables
        );
    }
}
