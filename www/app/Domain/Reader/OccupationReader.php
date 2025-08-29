<?php

namespace App\Domain\Reader;

use App\Domain\Object\Occupation\OccupationForSearch;

interface OccupationReader
{
    /**
     * @param int[] $occupationIds
     * @return string[]
     */
    public function findOccupationIdToNameByOccupationIds(array $occupationIds): array;

    /**
     * @param string[] $searches
     * @return int[]
     */
    public function findSearchToOccupationIdBySearches(array $searches): array;

    /**
     * @param int[] $curriculumIds
     * @return \App\Domain\Object\Occupation\OccupationForCount[]
     */
    public function findOccupationsForCountByCurriculumIds(array $curriculumIds): array;

    /**
     * @return \App\Domain\Object\Occupation\OccupationForForm[]
     */
    public function findOccupationsForForm(): array;
}
