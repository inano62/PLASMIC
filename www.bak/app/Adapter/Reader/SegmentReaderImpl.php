<?php

namespace App\Adapter\Reader;

use App\Domain\Object\Segment\SegmentForSingle;
use App\Domain\Reader\SegmentReader;
use App\Infrastructure\Dao\SegmentDao;

class SegmentReaderImpl implements SegmentReader
{
    /**
     * @param int $segmentsetId
     * @return array
     */
    public function findSegmentsForSingle(int $segmentsetId): array
    {
       return SegmentDao::select('type','color','value01')
           ->where('segmentsetId',$segmentsetId)
           ->get()
           ->map(function($data) {
               $colorHex =  '#' . str_pad(dechex($data->color), 6, 0, STR_PAD_LEFT);
               return new SegmentForSingle(
                   $data->type,
                   $colorHex,
                   $data->value01,
               );
           })->toArray();
    }
}
