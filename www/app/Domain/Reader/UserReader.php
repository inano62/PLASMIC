<?php

namespace App\Domain\Reader;

use App\Domain\Object\User\Authenticated;
use App\Domain\Object\User\UserForCurriculumApplication;
use App\Domain\Object\User\UserForCurriculumCart;
use App\Domain\Object\User\UserForNotify;
use App\Domain\Object\User\UserForSegment;
use App\Domain\Object\User\UserIndexQuery;
use App\Domain\Object\User\UserSearchQuery;

interface UserReader
{
    /**
     * @return int
     */
    public function findEnabledCount();

    /**
     * @return int
     */
    public function findDisabledCount();

    /**
     * @return int
     */
    public function findDuplicatedCount();

    /**
     * @param int $userId
     * @return bool
     */
    public function findIsEnabledByUserId(int $userId): bool;

    /**
     * @param int[] $userIds
     * @return bool
     */
    public function findIsEnabledByUserIds(array $userIds): bool;

    /**
     * @param int[] $userIds
     * @return string[]
     */
    public function findEmailsByUserIds(array $userIds): array;

    /**
     * @param int $userId
     * @return ?string
     */
    public function findDisplayNameByUserId(int $userId): ?string;

    /**
     * @param int $userId
     * @return bool
     */
    public function findHasPositionByUserId(int $userId): bool;

    /**
     * @param string $email
     * @return ?int
     */
    public function findUserIdByEmail(string $email): ?int;

    /**
     * @param string $email
     * @return ?int
     */
    public function findUserIdWhereDuplicatedByEmail(string $email): ?int;

    /**
     * @param int $userId
     * @return ?string
     */
    public function findHonmuBumonCodeByUserId(int $userId): ?string;

    /**
     * @param string[] $honmuBumonCodes
     * @return int
     */
    public function findCountByHonmuBumonCodes(array $honmuBumonCodes): int;

    /**
     * @param string[] $honmuBumonCodes
     * @return int[]
     */
    public function findUserIdsByHonmuBumonCodes(array $honmuBumonCodes): array;

    /**
     * @param int $userId
     * @return ?Authenticated
     */
    public function findAuthenticatedByUserId(int $userId): ?Authenticated;

    /**
     * @param string $email
     * @return ?Authenticated
     */
    public function findAuthenticatedByEmail(string $email): ?Authenticated;

    /**
     * @param int[] $userIds
     * @return string[]
     */
    public function findUserIdToDisplayNameByUserIds(array $userIds): array;

    /**
     * @param int[] $userIds
     * @return UserForSearch[]
     */
    public function findUserIdToUserForSearchByUserIds(array $userIds): array;

    /**
     * @param string[] $employeeCodes
     * @return int[]
     */
    public function findUserIdsByEmployeeCodes(array $employeeCodes): array;

    /**
     * @param string[] $employeeCodes
     * @return int[]
     */
    public function findEmployeeCodeToUserIdByEmployeeCodes(array $employeeCodes): array;

    /**
     * @param string[] $emails
     * @return int[]
     */
    public function findEmailToUserIdByEmails(array $emails): array;

    /**
     * @param int[] $exceptUserIds
     * @return int[]
     */
    public function findUserIdsByExceptUserIds(array $exceptUserIds): array;

    /**
     * @param int $userId
     * @return ?array
     */
    public function findDataForImportByUserId(int $userId): ?array;

    /**
     * @param int[] $userIds
     * @return array[]
     */
    public function findUserIdToDataForImportByUserIds(array $userIds): array;

    /**
     * @param int $userId
     * @return ?UserForCurriculumApplication
     */
    public function findUserForCurriculumApplicationByUserId(int $userId): ?UserForCurriculumApplication;

    /**
     * @param int $userId
     * @param bool $hasTelephone
     * @param bool $hasAddress
     * @return ?UserForCurriculumCart
     */
    public function findUserForCurriculumCartByUserId(int $userId, bool $hasTelephone, bool $hasAddress): ?UserForCurriculumCart;

    /**
     * @param int $userId
     * @return ?UserForNotify
     */
    public function findUserForNotifyByUserId(int $userId): ?UserForNotify;

    /**
     * @param int $userId
     * @return ?UserForSegment
     */
    public function findUserForSegmentByUserId(int $userId): ?UserForSegment;

    /**
     * @param UserIndexQuery $query
     * @return UserForIndex[]
     */
    public function findUsersForIndexByQuery(UserIndexQuery $query): array;

    /**
     * @param UserSearchQuery $query
     * @return UserForSearch[]
     */
    public function findUsersForSearchByQuery(UserSearchQuery $query): array;
}
