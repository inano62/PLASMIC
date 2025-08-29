<?php

namespace App\View\Console\Command;

use App\Application\InputData\Timer\MorningInputData;

class MorningCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected $signature = 'morning';

    /**
     * @var string
     */
    protected $description = 'Morning Command';

    /**
     * @return mixed
     */
    public function handle()
    {
        $inputData = new MorningInputData(time());
        $this->handleUseCase($inputData);
    }
}
