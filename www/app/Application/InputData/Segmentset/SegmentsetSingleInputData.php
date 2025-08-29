<?php

namespace App\Application\InputData\Segmentset;

use App\Application\InputData\AbstractInputData;

class SegmentsetSingleInputData extends AbstractInputData
{
    /**
     * @param array? $args
     * @return string[]
     */
    public function validator(array $args): array
    {
        return [
            'segmentsetId' => 'required|integer',
        ];
    }

    /**
     * @param array $input
     * @param array $args
     * @return void
     */
    public function setUp(array $input, array $args)
    {
        $this->segmentsetId = $input['segmentsetId'];
    }
}
