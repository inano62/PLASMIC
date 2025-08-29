<?php

namespace App\Domain\Object\SurveyCampaign;

class SurveyAnswerForImport
{
    /**
     * @return void
     */
    public function __construct(
        public readonly int $uid,
        public readonly int $employeeCode,
        public readonly int $time,
        public readonly int $quadrant,
    )
    {
    }
}
