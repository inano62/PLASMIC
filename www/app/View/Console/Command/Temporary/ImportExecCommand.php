<?php

namespace App\View\Console\Command\Temporary;

use App\Application\InputData\Import\ImportExecInputData;

class ImportExecCommand extends AbstractCommand
{
    /**
     * @return void
     */
    public function handle()
    {
        ini_set("memory_limit", "512M");

        $importId = 2;

        $inputData = new ImportExecInputData([
            'importId' => $importId,
        ], time());
        $this->handleUseCase($inputData);
    }
}
