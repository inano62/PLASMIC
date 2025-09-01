<?php

namespace App\Application\InputData\Downloadable;

use App\Application\InputData\AbstractInputData;

class DownloadableAttachmentInputData extends AbstractInputData
{
    /**
     * @param array? $args
     * @return string[]
     */
    public function validator(array $args): array
    {
        return [
            'id' => 'required|integer',
        ];
    }

    /**
     * @param array $input
     * @param array? $args
     * @return void
     */
    public function setUp($input, $args)
    {
        $this->downloadableId = (int)$input['id'];
    }
}
