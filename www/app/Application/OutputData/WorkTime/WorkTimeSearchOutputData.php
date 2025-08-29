<?php

namespace App\Application\OutputData\WorkTime;

use App\Application\OutputData\AbstractOutputData;

class WorkTimeSearchOutputData extends AbstractOutputData
{
    public function __construct(
        public readonly array $users,
        public readonly array $bushos,
    )
    {
    }
}
