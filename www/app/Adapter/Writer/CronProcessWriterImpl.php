<?php

namespace App\Adapter\Writer;

use App\Domain\Constant\CronProcessConstant;
use App\Domain\Object\CronProcess\CurrentProcess;
use App\Domain\Writer\CronProcessWriter;
use App\Infrastructure\Dao\CronProcessDao;

class CronProcessWriterImpl implements CronProcessWriter
{
    /**
     * @param string $name
     * @param int $time
     * @return CurrentProcess
     */
    public function insert(string $name, int $time): CurrentProcess
    {
        CronProcessDao::create([
            'name' => $name,
            'begunAt' => date('Y-m-d H:i:s', $time),
        ]);

        return new CurrentProcess(CronProcessConstant::STATUS_STOPPED, $time);
    }

    /**
     * @param int $time
     * @return void
     */
    public function begin(string $name, int $time)
    {
        CronProcessDao::where('name', $name)->update([
            'status' => CronProcessConstant::STATUS_RUNNING,
            'begunAt' => date('Y-m-d H:i:s', $time),
        ]);
    }

    /**
     * @access private
     * @param int $time
     * @return void
     */
    public function succeed(string $name, int $time)
    {
        CronProcessDao::where('name', $name)->update([
            'status' => CronProcessConstant::STATUS_STOPPED,
            'succeededAt' => date('Y-m-d H:i:s', $time),
        ]);
    }

    /**
     * @param string $name
     * @param int $time
     * @return void
     */
    public function fail(string $name, int $time)
    {
        CronProcessDao::where('name', $name)->update([
            'status' => CronProcessConstant::STATUS_STOPPED,
            'failedAt' => date('Y-m-d H:i:s', $time),
        ]);
    }
}
