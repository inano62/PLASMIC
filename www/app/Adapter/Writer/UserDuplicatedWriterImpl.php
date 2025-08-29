<?php

namespace App\Adapter\Writer;

use App\Domain\Constant\UserDuplicatedConstant;
use App\Domain\Writer\UserDuplicatedWriter;
use App\Infrastructure\Dao\UserDuplicatedDao;

class UserDuplicatedWriterImpl implements UserDuplicatedWriter
{
    /**
     * @param \App\Domain\Object\UserDuplicated\UserDuplicatedForImportCreate[] $users
     * @return void
     */
    public function importCreateBulk(array $users)
    {
        foreach ($users as $user) {
             UserDuplicatedDao::create(array_merge(
                $user->data,
                ['status' => UserDuplicatedConstant::STATUS_UNTREATED],
            ))->id;
        }
    }

    /**
     * @param \App\Domain\Object\UserDuplicated\UserDuplicatedForImportUpdate[] $users
     * @return void
     */
    public function importUpdateBulk(array $users)
    {
        foreach ($users as $user) {
            UserDuplicatedDao::where('id', $user->userDuplicatedId)->update(array_merge(
                $user->data,
                ['status' => UserDuplicatedConstant::STATUS_UNTREATED],
            ));
        }
    }

    /**
     * @param string $email
     * @return void
     */
    public function deleteByEmail(string $email)
    {
        return UserDuplicatedDao::where('email', $email)->delete();
    }
}
