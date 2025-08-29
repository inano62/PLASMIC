<?php

namespace App\Application\UseCase\Downloadable;

use App\Application\InputData\Downloadable\DownloadableAttachmentInputData;
use App\Application\OutputData\Downloadable\DownloadableAttachmentOutputData;
use App\Domain\Reader\DownloadableReader;

class DownloadableAttachmentUseCase
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
     * @param DownloadableAttachmentInputData $inputData
     * @return DownloadableAttachmentOutputData
     */
    public function handle(DownloadableAttachmentInputData $inputData): DownloadableAttachmentOutputData
    {
        $downloadableId = $inputData->downloadableId;

        $downloadable = $this->downloadableReader->findDownloadableForAttachmentByDownloadableId($downloadableId);

        return new DownloadableAttachmentOutputData(
            $downloadable
        );
    }
}
