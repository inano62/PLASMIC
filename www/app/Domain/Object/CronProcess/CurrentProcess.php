<?php

namespace App\Domain\Object\CronProcess;

class CurrentProcess
{
    /**
     * @param string $status
     * @param int $begunTime
     * @return void
     */
    public function __construct(
        string $status,
        int $begunTime
    )
    {
        $this->status = $status;
        $this->begunTime = $begunTime;
    }
}
