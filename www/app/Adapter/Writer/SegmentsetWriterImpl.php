<?php

namespace App\Adapter\Writer;

use App\Domain\Writer\SegmentsetWriter;
use App\Infrastructure\Dao\SegmentsetDao;
use App\Infrastructure\Dao\SegmentDao;

class SegmentsetWriterImpl implements SegmentsetWriter
{
    /**
     * @param int|null $segmentsetId
     * @param string $name
     * @param array $segments
     * @return void
     */
    public function update(?int $segmentsetId, string $name, array $segments)
    {
        \DB::beginTransaction();

        if(is_null($segmentsetId)){
            $createSegmentsetId = SegmentsetDao::create([
                'name' => $name,
                'order' => 1,
            ])->id;
        }else{
            $createSegmentsetId = $segmentsetId;
            SegmentsetDao::where('id', $segmentsetId)->update([
                'name' => $name,
            ]);
            SegmentDao::where('segmentsetId', $segmentsetId)->delete();
        }

        foreach ($segments as $segment){
            $colorDec = hexdec(str_replace('#', '', $segment->color));
            SegmentDao::create([
                'segmentsetId' => $createSegmentsetId,
                'type' => $segment->type,
                'color' => $colorDec,
                'value01' => $segment->value01,
                'order' => 1,
            ]);
        }

        \DB::commit();
    }

    /**
     * @param int $segmentsetId
     * @return void
     */
    public function delete(int $segmentsetId)
    {
        \DB::beginTransaction();

        SegmentDao::where('segmentsetId', $segmentsetId)->delete();
        SegmentsetDao::where('id', $segmentsetId)->delete();

        \DB::commit();
    }
}
