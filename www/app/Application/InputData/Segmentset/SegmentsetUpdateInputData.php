<?php

namespace App\Application\InputData\Segmentset;

use App\Application\InputData\AbstractInputData;
use App\Application\InputData\Segmentset\SegmentsetUpdateFieldset;
use App\Domain\Constant\SegmentConstant;

class SegmentsetUpdateInputData extends AbstractInputData
{
    /**
     * @param array? $args
     * @return string[]
     */
    public function validator(array $args): array
    {
        return [
            'segmentsetId' => 'nullable|integer',
            'name' => 'required|string',
            'segments' => 'required|array',
            'segments.*' => 'required|array',
            'segments.*.type' => 'required|integer|in:'
                .SegmentConstant::TYPE_MAN.','
                .SegmentConstant::TYPE_WOMAN.','
                .SegmentConstant::TYPE_NEW_GRADUATE.','
                .SegmentConstant::TYPE_CAREER.',',
            'segments.*.color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'segments.*.value01' => 'nullable|numeric',
        ];
    }

    /**
     * @param array $input
     * @param array? $args
     * @return void
     */
    public function setUp(array $input, array $args)
    {
        $this->segmentsetId = !empty($input['segmentsetId']) ? (int)$input['segmentsetId'] : NULL;
        $this->name = $input['name'];
        $this->segments = array_map(function($data) {
            return new SegmentsetUpdateFieldset(
                (int)$data['type'],
                $data['color'],
                !empty($data['value01']) ? (float)$data['value01'] : NULL
            );
        }, $input['segments']);
    }
}
