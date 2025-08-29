<?php

namespace App\View\Console\Command;

use App\Application\InputData\Timer\MonthInputData;

class MonthCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected $signature = 'month';

    /**
     * @var string
     */
    protected $description = 'Month Command';

    /**
     * @return mixed
     */
    public function handle()
    {
        $inputData = new MonthInputData(time());
        $this->handleUseCase($inputData);
    }
}
