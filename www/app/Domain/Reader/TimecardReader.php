<?php

namespace App\Domain\Reader;

use App\Domain\Object\WorkTime\WorkTimeSearchCondition;

interface TimecardReader
{
    public function findUsersForWorkTime(WorkTimeSearchCondition $SearchCondition): array;
}
