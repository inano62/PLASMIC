<?php

namespace App\Application\InputData\Import;

use App\Application\InputData\AbstractInputData;

class ImportSearchInputData extends AbstractInputData
{
    public function validator(array $args): array
    {
        return [
        ];
    }

    /**
     * @param array $input
     * @param array? $args
     * @return void
     */
    public function setUp($input, $args)
    {
    }
}
