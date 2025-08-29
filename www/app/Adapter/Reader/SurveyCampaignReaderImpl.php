<?php

namespace App\Adapter\Reader;

use App\Domain\Object\SurveyCampaign\SurveyCampaignForSearch;
use App\Domain\Reader\SurveyCampaignReader;
use App\Infrastructure\Dao\SurveyCampaignDao;

class SurveyCampaignReaderImpl implements SurveyCampaignReader
{
    /**
     * @param int $surveyCampaignId
     * @return ?string
     */
    public function findNameBySurveyCampaignId(int $surveyCampaignId): ?string
    {
        return SurveyCampaignDao::where('id', $surveyCampaignId)->value('name');
    }

    /**
     * @return array
     */
    public function findSurveyCampaignsForSearch(): array
    {
        return SurveyCampaignDao::select('id', 'name')->get()->map(function($data) {
            return new SurveyCampaignForSearch(
                $data->id,
                $data->name
            );
        })->toArray();
    }

    /**
     * @param int $timeFrom
     * @param int $timeTo
     * @return int|null
     */
    public function findSurveyCampaignIdByPeriod(int $timeFrom, int $timeTo): ?int
    {
        $minDate = (int)date('Ymd', $timeFrom);
        $maxDate = (int)date('Ymd', $timeTo);

        return SurveyCampaignDao::where('dateFrom','<=',$minDate)->where('dateTo','>=',$maxDate)->value('id');
    }
}
