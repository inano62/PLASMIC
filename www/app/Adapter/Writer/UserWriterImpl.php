<?php

namespace App\Adapter\Writer;

use App\Domain\Constant\UserConstant;
use App\Domain\Object\User\UserForUpdate;
use App\Domain\Object\User\UserImportColumnGroup;
use App\Domain\Writer\UserWriter;
use App\Infrastructure\Dao\UserDao;
use App\Infrastructure\Dao\UserHistoryDao;

class UserWriterImpl implements UserWriter
{
    /**
     * @param int[] $userIds
     * @return void
     */
    public function updateStatusEnabledByUserIds(array $userIds)
    {
        foreach (array_chunk($userIds, 900) as $chunk) {
            UserDao::whereIn('id', $chunk)->update([
                'status' => UserConstant::STATUS_ENABLED,
            ]);
        }
    }

    /**
     * @param int[] $userIds
     * @return void
     */
    public function updateStatusDisabledByUserIds(array $userIds)
    {
        foreach (array_chunk($userIds, 900) as $chunk) {
            UserDao::whereIn('id', $userIds)->update([
                'status' => UserConstant::STATUS_DISABLED,
            ]);
        }
    }

    /**
     * @param UserForUpdate $user
     * @return void
     */
    public function update(UserForUpdate $user)
    {
        $userId = $user->userId;

        \DB::beginTransaction();

        UserDao::where('id', $userId)->update([
            'email' => $user->email,
        ]);

        UserHistoryDao::create(array_merge(
            $user->data,
            ['userId' => $userId]
        ));

        \DB::commit();
    }

    /**
     * @param int[] $userIds
     * @return void
     */
    public function updateStatusDuplicatedByUserIds(array $userIds)
    {
        foreach (array_chunk($userIds, 900) as $chunk) {
            UserDao::whereIn('id', $chunk)->update([
                'status' => UserConstant::STATUS_DUPLICATED,
            ]);
        }
    }

    /**
     * @param \App\Domain\Object\User\UserForImportCreate[] $users
     * @param int $importId
     * @param int $time
     * @return void
     */
    public function importCreateBulk(array $users, int $importId, int $time)
    {
        $userIdToData = [];
        
        foreach ($users as $user) {

            $userId = UserDao::create(array_merge(
                $user->data,
                $user->isUpdatedSocialAge ? ['socialAgeAt' => date('Y-m-d H:i:s', $time)] : []
            ))->id;

            UserHistoryDao::create(array_merge(
                $user->data,
                ['userId' => $userId, 'importId' => $importId]
            ));
        }
    }

    /**
     * @param \App\Domain\Object\User\UserForImportCreate[] $users
     * @param int $importId
     * @param int $time
     * @return void
     */
    public function importUpdateBulk(array $users, int $importId, int $time)
    {
        foreach ($users as $user) {

            UserDao::where('id', $user->userId)->update(array_merge(
                $user->data,
                ['status' => UserConstant::STATUS_ENABLED],
                $user->isUpdatedSocialAge ? ['socialAgeAt' => date('Y-m-d H:i:s', $time)] : []
            ));

            UserHistoryDao::create(array_merge(
                $user->previousData,
                $user->data,
                ['userId' => $user->userId, 'importId' => $importId]
            ));
        }
    }
}
