<?php

namespace App\Application\InputData\Microsoft;

use App\Application\InputData\AbstractInputData;

class MicrosoftCallbackInputData extends AbstractInputData
{
    /**
     * @param array? $args
     * @return string[]
     */
    public function validator(array $args): array
    {
        return [
            'code' => 'required|string',
        ];
    }

    /**
     * @param array $input
     * @param array? $args
     * @return void
     */
    public function setUp($input, $args)
    {
        $this->code = $input['code'];
    }
}
