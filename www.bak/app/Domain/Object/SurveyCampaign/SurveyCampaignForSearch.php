<?php

namespace App\Domain\Object\SurveyCampaign;

class SurveyCampaignForSearch
{
    /**
     * @return void
     */
    public function __construct(
        public readonly int $surveyCampaignId,
        public readonly string $name,
    )
    {
    }
}
