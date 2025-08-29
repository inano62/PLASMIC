<?php

namespace App\Application\InputData\Import;

use App\Application\InputData\AbstractInputData;

class ImportUploadInputData extends AbstractInputData
{
    /**
     * @param array? $args
     * @return string[]
     */
    public function validator(array $args): array
    {
        return [
            'file' => 'required|file',
            'type' => 'required|integer'
        ];
    }

    /**
     * @param array $input
     * @param array? $args
     * @return void
     */
    public function setUp($input, $args)
    {
        $this->file = $input['file'];
        $this->type = (int)$input['type'];
    }
}
