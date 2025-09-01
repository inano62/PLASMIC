<?php

namespace App\Domain\Service\User;

use App\Domain\Object\User\UserForImportUpdate;
use App\Domain\Object\User\UserImportColumnGroup;
use App\Domain\Object\UserDuplicated\UserDuplicatedForImportCreate;
use App\Domain\Object\UserDuplicated\UserDuplicatedForImportUpdate;

interface UserImportService
{
    /**
     * @param UserImportColumnGroup $columnGroup
     * @return bool
     */
    public function isValidUserImportColumnGroupIfStrict(UserImportColumnGroup $columnGroup): bool;

    /**
     * @param string[][] $rows
     * @param UserImportColumnGroup $columnGroup
     * @param int[] $contractTypeNameToId
     * @param int[] $joinWayNameToId
     * @param int[] $occupationNameToId
     * @param int[] $positionNameToId
     * @param int[] $roleNameToId
     * @return ?string[][] $rows
     */
    public function replaceNameToIdInRows(array $rows, UserImportColumnGroup $columnGroup, array $contractTypeNameToId, array $joinWayNameToId, array $occupationNameToId, array $positionNameToId, array $roleIdToId): array;
        
    /**
     * @param string[][] $rows
     * @param UserImportColumnGroup $columnGroup
     * @param int $way
     * @param int[] $employeeCodeToUserId
     * @param int[] $emailToUserId
     * @return array
     */
    public function parse(array $rows, UserImportColumnGroup $columnGroup, int $way, array $employeeCodeToUserId, array $emailToUserId): array;

    /**
     * @param UserForImportUpdate $user
     * @param array $data
     * @return ?UserForImportUpdate
     */
    public function calculateUserForImportUpdate(UserForImportUpdate $user, array $data): ?UserForImportUpdate;

    /**
     * @param array $data
     * @return UserDuplicatedForImportCreate
     */
    public function calcualteUserDuplicatedForImportCreate(array $data): UserDuplicatedForImportCreate;

    /**
     * @param int $userDuplicatedId
     * @param array $data
     * @return UserDuplicatedForImportUpdate
     */
    public function calcualteUserDuplicatedForImportUpdate(int $userDuplicatedId, array $data): UserDuplicatedForImportUpdate;
}
