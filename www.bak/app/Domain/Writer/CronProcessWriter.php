<?php

namespace App\Domain\Writer;

use App\Domain\Object\CronProcess\CurrentProcess;

interface CronProcessWriter
{
    /**
     * @param string $name
     * @param int $time
     * @return CurrentProcess
     */
    public function insert(string $name, int $time): CurrentProcess;

    /**
     * @param string $name
     * @param int $time
     * @return void
     */
    public function begin(string $name, int $time);

    /**
     * @param string $name
     * @param int $time
     * @return void
     */
    public function succeed(string $name, int $time);

    /**
     * @param string $name
     * @param int $time
     * @return void
     */
    public function fail(string $name, int $time);
}
