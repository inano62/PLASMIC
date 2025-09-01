<?php

namespace App\Domain\Reader;

use App\Domain\Object\SurveyCampaign\SurveyCampaignForSearch;

interface SurveyCampaignReader
{
    /**
     * @param int $surveyCampaignId
     * @return ?string
     */
    public function findNameBySurveyCampaignId(int $surveyCampaignId): ?string;

    /**
     * @return array
     */
    public function findSurveyCampaignsForSearch(): array;

    /**
     * @param int $timeFrom
     * @param int $timeTo
     * @return int|null
     */
    public function findSurveyCampaignIdByPeriod(int $timeFrom, int $timeTo): ?int;
}
