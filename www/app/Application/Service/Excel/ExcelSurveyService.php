<?php

namespace App\Application\Service\Excel;

use App\Domain\Object\SurveyCampaign\SurveyCampaignForImport;

interface ExcelSurveyService
{
    /**
     * @param string $content
     * @return SurveyCampaignForImport|null
     */
    public function readForImport(string $content): ?SurveyCampaignForImport;
}
