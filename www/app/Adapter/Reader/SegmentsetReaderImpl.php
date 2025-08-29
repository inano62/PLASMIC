<?php

namespace App\Adapter\Reader;

use App\Domain\Object\Segmentset\SegmentsetForSearch;
use App\Domain\Object\Segmentset\SegmentsetForSingle;
use App\Domain\Reader\SegmentsetReader;
use App\Infrastructure\Dao\SegmentsetDao;

class SegmentsetReaderImpl implements SegmentsetReader
{
    /**
     * @return array
     */
    public function findSegmentsetsForSearch(): array
    {
        return SegmentsetDao::select('id', 'name')->get()->map(function($data) {
            return new SegmentsetForSearch(
                $data->id,
                $data->name
            );
        })->toArray();
    }

    /**
     * @param int $segmentsetId
     * @return SegmentsetForSingle|null
     */
    public function findSegmentsetForSingle(int $segmentsetId): ?SegmentsetForSingle
    {
        $data = SegmentsetDao::select('id','name')->where('id',$segmentsetId)->first();

        if (is_null($data)) { return NULL; }

        return new SegmentsetForSingle($data->id,$data->name);
    }
}
