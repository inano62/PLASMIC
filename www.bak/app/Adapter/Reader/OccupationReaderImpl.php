<?php

namespace App\Adapter\Reader;

use App\Domain\Constant\CurriculumApplicationConstant;
use App\Domain\Reader\OccupationReader;
use App\Domain\Object\Occupation\OccupationForCount;
use App\Domain\Object\Occupation\OccupationForForm;
use App\Infrastructure\Dao\CurriculumApplicationDao;
use App\Infrastructure\Dao\OccupationDao;
use App\Infrastructure\Dao\UserDao;

class OccupationReaderImpl implements OccupationReader
{
    /**
     * @param int[] $occupationIds
     * @return string[]
     */
    public function findOccupationIdToNameByOccupationIds(array $occupationIds): array
    {
        $occupationIdToName = [];
        foreach (array_chunk($occupationIds, 900) as $chunk) {
            foreach (OccupationDao::select('id', 'name')->whereIn('id', $chunk)->get() as $data) {
                $occupationIdToName[$data->id] = $data->name;
            }
        }
        return $occupationIdToName;
    }

    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToOccupationIdBySearches(array $searches): array
    {
        $searchToOccupationId = [];

        foreach (array_chunk($searches, 900) as $chunk) {

            foreach (OccupationDao::select('id', 'search')->whereIn('search', $chunk)->get() as $data) {
                $searchToOccupationId[$data->search] = $data->id;
            }
        }

        if (in_array(NULL, $searches, TRUE)) {
            $occupationId = OccupationDao::where('search', '未登録')->value('id');
            if (!is_null($occupationId)) { $searchToOccupationId[NULL] = $occupationId; }
        }

        return $searchToOccupationId;
    }

    /**
     * @param int[] $curriculumIds
     * @return \App\Domain\Object\Occupation\OccupationForCount[]
     */
    public function findOccupationsForCountByCurriculumIds(array $curriculumIds): array
    {
        $builder = CurriculumApplicationDao::select('occupationId', \DB::raw('COUNT(*) AS count'))->whereIn('curriculumId', $curriculumIds)->where("status", CurriculumApplicationConstant::STATUS_APPROVED)->groupBy('occupationId');

        $occupationIdToCount = [];

        foreach ($builder->get() as $data) {
            $occupationId = $data->occupationId;

            if (!isset($occupationIdToCount[$occupationId])) { $occupationIdToCount[$occupationId] = 0; }

            $occupationIdToCount[$occupationId] += $data->count;
        }

        return OccupationDao::select('id', 'name')->orderBy('order')->get()->map(function($data) use($occupationIdToCount) {
            return new OccupationForCount(
                $data->id,
                $data->name,
                isset($occupationIdToCount[$data->id]) ? $occupationIdToCount[$data->id] : 0
            );
        })->sortBy(function($occupation) {
            return - $occupation->count;
        })->values()->toArray();
    }

    /**
     * @return \App\Domain\Object\Occupation\OccupationForForm[]
     */
    public function findOccupationsForForm(): array
    {
        return OccupationDao::select('id', 'name')->orderBy('order')->get()->map(function($data) {
            return new OccupationForForm(
                $data->id,
                $data->name
            );
        })->toArray();
    }
}
