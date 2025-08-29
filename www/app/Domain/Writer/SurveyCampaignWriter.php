<?php

namespace App\Domain\Writer;

use App\Domain\Object\SurveyCampaign\SurveyCampaignForImport;

interface SurveyCampaignWriter
{
    /**
     * @param SurveyCampaignForImport $campaign
     * @param int $surveyCampaignId
     */
    public function import(SurveyCampaignForImport $campaign, int $surveyCampaignId);
}
