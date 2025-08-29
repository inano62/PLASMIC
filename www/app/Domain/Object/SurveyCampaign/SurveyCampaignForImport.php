<?php

namespace App\Domain\Object\SurveyCampaign;

class SurveyCampaignForImport
{
    /**
     * @return void
     */
    public function __construct(
        public readonly int $timeFrom,
        public readonly int $timeTo,
        public readonly array $answers
    )
    {
    }
}
