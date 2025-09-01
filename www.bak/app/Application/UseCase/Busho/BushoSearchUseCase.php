<?php

namespace App\Application\UseCase\Busho;

use App\Application\InputData\Busho\BushoSearchInputData;
use App\Application\OutputData\Busho\BushoSearchOutputData;
use App\Domain\Reader\BumonReader;
use App\Domain\Reader\BushoReader;

class BushoSearchUseCase
{
    /**
     * @return void
     */
    public function __construct(
        public readonly BumonReader $bumonReader,
        public readonly BushoReader $bushoReader
    )
    {
    }

    /**
     * @param BushoSearchInputData $inputData
     * @return BushoSearchOutputData
     */
    public function handle(BushoSearchInputData $inputData): BushoSearchOutputData
    {
        $bumons = $this->bumonReader->findBumonsForSearch();
        $bushos = $this->bushoReader->findBushosForSearch();

        return new BushoSearchOutputData(
            $bumons,
            $bushos,
        );
    }
}
