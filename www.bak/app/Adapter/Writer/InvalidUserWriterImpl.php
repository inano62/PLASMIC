<?php

namespace App\Adapter\Writer;

use App\Domain\Object\InvalidUser\InvalidUserForImport;
use App\Domain\Writer\InvalidUserWriter;
use App\Infrastructure\Dao\InvalidUserDao;

class InvalidUserWriterImpl implements InvalidUserWriter
{
    /**
     * @param \App\Domain\Object\InvalidUser\InvalidUserForImport $invalidUsers
     * @return void
     */
    public function importBulk(array $invalidUsers)
    {
        \DB::beginTransaction();

        foreach ($invalidUsers as $invalidUser) {
            InvalidUserDao::create($invalidUser->update);
        }

        \DB::commit();
    }
}
