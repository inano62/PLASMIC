<?php

namespace App\Application\InputData\Import;

use App\Application\InputData\AbstractInputData;

class ImportExecInputData extends AbstractInputData
{
    /**
     * @param array? $args
     * @return string[]
     */
    public function validator(array $args): array
    {
        return [
            'importId' => 'required|integer',
        ];
    }

    /**
     * @param array $input
     * @param array? $args
     * @return void
     */
    public function setUp($input, $args)
    {
        list($time) = $args;

        $this->time = $time;
        $this->importId = (int)$input['importId'];
    }
}
