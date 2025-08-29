<?php

namespace App\Domain\Writer;

use App\Domain\Object\User\UserForUpdate;
use App\Domain\Object\User\UserImportColumnGroup;

interface UserWriter
{
    /**
     * @param int[] $userIds
     * @return void
     */
    public function updateStatusEnabledByUserIds(array $userIds);

    /**
     * @param int[] $userIds
     * @return void
     */
    public function updateStatusDisabledByUserIds(array $userIds);

    /**
     * @param int[] $userIds
     * @return void
     */
    public function updateStatusDuplicatedByUserIds(array $userIds);

    /**
     * @param UserForUpdate $user
     * @return void
     */
    public function update(UserForUpdate $user);

    /**
     * @param \App\Domain\Object\User\UserForImportCreate[] $users
     * @param int $userImportId
     * @param int $time
     * @return void
     */
    public function importCreateBulk(array $users, int $userImportId, int $time);

    /**
     * @param \App\Domain\Object\User\UserForImportCreate[] $users
     * @param int $userImportId
     * @param int $time
     * @return void
     */
    public function importUpdateBulk(array $users, int $userImportId, int $time);
}
