<?php

namespace App\Adapter\Reader;

use App\Adapter\Service\String\StringKeywordServiceImpl;
use App\Domain\Constant\UserConstant;
use App\Domain\Object\User\Authenticated;
use App\Domain\Object\User\PasswordAuthenticated;
use App\Domain\Object\User\UserForCurriculumApplication;
use App\Domain\Object\User\UserForCurriculumCart;
use App\Domain\Object\User\UserForNotify;
use App\Domain\Object\User\UserForIndex;
use App\Domain\Object\User\UserForSearch;
use App\Domain\Object\User\UserForSegment;
use App\Domain\Object\User\UserIndexQuery;
use App\Domain\Object\User\UserSearchQuery;
use App\Domain\Reader\UserReader;
use App\Infrastructure\Dao\UserDao;

class UserReaderImpl implements UserReader
{
    /**
     * @return int
     */
    public function findEnabledCount()
    {
        return UserDao::where('status', UserConstant::STATUS_ENABLED)->count();
    }

    /**
     * @return int
     */
    public function findDisabledCount()
    {
        return UserDao::where('status', UserConstant::STATUS_DISABLED)->count();
    }

    /**
     * @return int
     */
    public function findDuplicatedCount()
    {
        return UserDao::where('status', UserConstant::STATUS_DUPLICATED)->count();
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function findIsEnabledByUserId(int $userId): bool
    {
        return !!UserDao::where('id', $userId)->whereEnabled()->count();
    }

    /**
     * @param int[] $userIds
     * @return bool
     */
    public function findIsEnabledByUserIds(array $userIds): bool
    {
        $count = 0;
        foreach (array_chunk($userIds, 900) as $chunk) {
            $count += UserDao::whereIn('id', $chunk)->whereEnabled()->count();
        }
        return $count === count($userIds);
    }

    /**
     * @param int[] $userIds
     * @return string[]
     */
    public function findEmailsByUserIds(array $userIds): array
    {
        $emails = [];
        foreach (array_chunk($userIds, 900) as $chunk) {
            $emails =  array_merge(
                $emails,
                UserDao::where('id', $chunk)->pluck('email')->toArray()
            );
        }

        return $emails;
    }

    /**
     * @param int $userId
     * @return ?string
     */
    public function findDisplayNameByUserId(int $userId): ?string
    {
        return UserDao::where('id', $userId)->whereEnabled()->value('displayName');
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function findHasPositionByUserId(int $userId): bool
    {
        return !is_null(UserDao::where('id', $userId)->whereEnabled()->value('positionName'));
    }

    /**
     * @param string $email
     * @return ?int
     */
    public function findUserIdByEmail(string $email): ?int
    {
        return UserDao::where('email', $email)->whereEnabled()->value('id');
    }

    /**
     * @param string $email
     * @return ?int
     */
    public function findUserIdWhereDuplicatedByEmail(string $email): ?int
    {
        return UserDao::where('email', $email)->where('status', UserConstant::STATUS_DUPLICATED)->value('id');
    }

    /**
     * @param int $userId
     * @return ?string
     */
    public function findHonmuBumonCodeByUserId(int $userId): ?string
    {
        return UserDao::where('id', $userId)->whereEnabled()->value('honmuBumonCode');
    }

    /**
     * @param string[] $honmuBumonCodes
     * @return int
     */
    public function findCountByHonmuBumonCodes(array $honmuBumonCodes): int
    {
        $count = 0;
        foreach (array_chunk($honmuBumonCodes, 900) as $chunk) {
            $count += UserDao::whereIn('honmuBumonCode', $chunk)->whereEnabled()->count();
        }
        return $count;
    }

    /**
     * @param string[] $honmuBumonCodes
     * @return int[]
     */
    public function findUserIdsByHonmuBumonCodes(array $honmuBumonCodes): array
    {
        $userIds = [];
        foreach (array_chunk($honmuBumonCodes, 900) as $chunk) {
            $userIds = array_merge(
                $userIds,
                UserDao::whereIn('honmuBumonCode', $chunk)->whereEnabled()->pluck('id')->toArray()
            );
        }
        return $userIds;
    }

    /**
     * @param int $userId
     * @return ?Authenticated
     */
    public function findAuthenticatedByUserId(int $userId): ?Authenticated
    {
        $data = UserDao::select('id', 'displayName', 'permissions')->where('id', $userId)->whereEnabled()->first();
        if (is_null($data)) { return NULL; }

        $permissions = $this->calculateSessionParmissions($data->permissions);

        return new Authenticated(
            $data->id,
            $data->displayName,
            $permissions
        );
    }

    /**
     * @param string $email
     * @return ?Authenticated
     */
    public function findAuthenticatedByEmail(string $email): ?Authenticated
    {
        $userId = UserDao::where('email', $email)->whereEnabled()->value('id');
        if (is_null($userId)) { return NULL; }

        return $this->findAuthenticatedByUserId($userId);
    }

    /**
     * @access private
     * @param ?string $permissionsText
     * @return string[]
     */
    private function calculateSessionParmissions(?string $permissionsText)
    {
        return $permissionsText ? explode(',', $permissionsText) : [];
    }

    /**
     * @param int[] $userIds
     * @return string[]
     */
    public function findUserIdToDisplayNameByUserIds(array $userIds): array
    {
        $userIdToDisplayName = [];
        foreach (array_chunk($userIds, 900) as $chunk) {
            foreach (UserDao::select('id', 'displayName')->whereIn('id', $chunk)->get() as $data) {
                $userIdToDisplayName[$data->id] = $data->displayName;
            }
        }
        return $userIdToDisplayName;
    }

    /**
     * @param int[] $userIds
     * @return UserForSearch[]
     */
    public function findUserIdToUserForSearchByUserIds(array $userIds): array
    {
        $userIdToUser = [];
        foreach (array_chunk($userIds, 900) as $chunk) {

            foreach (UserDao::select('id', 'displayName', 'honmuBumonName', 'honmuBushoName')->whereIn('id', $chunk)->get() as $data) {
                $userIdToUser[$data->id] = new UserForSearch(
                    $data->id,
                    $data->displayName,
                    $data->honmuBumonName,
                    $data->honmuBushoName
                );
            }
        }

        return $userIdToUser;
    }

    /**
     * @param string[] $employeeCodes
     * @return int[]
     */
    public function findUserIdsByEmployeeCodes(array $employeeCodes): array
    {
        return array_unique(array_values($this->findEmployeeCodeToUserIdByEmployeeCodes($employeeCodes)));
    }

    /**
     * @param string[] $employeeCodes
     * @return int[]
     */
    public function findEmployeeCodeToUserIdByEmployeeCodes(array $employeeCodes): array
    {
        $employeeCodeToUserId = [];
        foreach (array_chunk($employeeCodes, 900) as $chunk) {
            foreach (UserDao::select('id', 'employeeCode')->whereIn('employeeCode', $chunk)->get() as $data) {
                $employeeCodeToUserId[$data->employeeCode] = $data->id;
            }
        }
        return $employeeCodeToUserId;
    }

    /**
     * @param string[] $emails
     * @return int[]
     */
    public function findEmailToUserIdByEmails(array $emails): array
    {
        $emailToUserId = [];
        foreach (array_chunk($emails, 900) as $chunk) {
            foreach (UserDao::select('id', 'email')->whereIn('email', $chunk)->whereEnabled()->get() as $data) {
                $emailToUserId[$data->email] = $data->id;
            }
        }
        return $emailToUserId;
    }

    /**
     * @param string[] $exceptUserIds
     * @return int[]
     */
    public function findUserIdsByExceptUserIds(array $exceptUserIds): array
    {
        return array_values(
            array_diff(UserDao::pluck('id')->toArray(), $exceptUserIds)
        );
    }

    /**
     * @param int $userId
     * @return ?array
     */
    public function findDataForImportByUserId(int $userId): ?array
    {
        $userIdToData = $this->findUserIdToDataForImportByUserIds([$userId]);

        return isset($userIdToData[$userId]) ? $userIdToData[$userId] : NULL;
    }

    /**
     * @param int[] $userIds
     * @return array[]
     */
    public function findUserIdToDataForImportByUserIds(array $userIds): array
    {
        $userIdToData = [];

        foreach (array_chunk($userIds, 900) as $chunk) {
            foreach (UserDao::select(
                'id',
                'contractTypeId',
                'joinWayId',
                'occupationId',
                'positionId',
                'roleId',
                'socialAge',
                'email',
                'displayName',
                'employeeCode',
                'surnameKanji',
                'forenameKanji',
                'surnameKatakana',
                'forenameKatakana',
                'surnameAlphabet',
                'forenameAlphabet',
                'gender',
                'bumonGroupCode',
                'bumonGroupName',
                'bumonCode',
                'bumonName',
                'bushoCode',
                'bushoName',
                'honmuCompanyName',
                'honmuBumonGroupCode',
                'honmuBumonGroupName',
                'honmuBumonCode',
                'honmuBumonName',
                'honmuBushoCode',
                'honmuBushoName',
            )->whereIn('id', $chunk)->get() as $data) {
                $userId = $data->id;
                $array = $data->toArray();
                unset($array['id']);

                $userIdToData[$userId] = $array;
            }
        }

        return $userIdToData;
    }

    /**
     * @param int $userId
     * @return ?UserForCurriculumApplication
     */
    public function findUserForCurriculumApplicationByUserId(int $userId): ?UserForCurriculumApplication
    {
        $data = UserDao::select('occupationId', 'displayName', 'honmuBumonName', 'positionName', 'socialAge')->where('id', $userId)->whereEnabled()->first();
        if (is_null($data)) { return NULL; }

        return new UserForCurriculumApplication(
            $userId,
            $data->displayName,
            !is_null($data->positionName),
            $data->occupationId,
            $data->honmuBumonName,
            $data->socialAge
        );
    }

    /**
     * @param int $userId
     * @param bool $hasTelephone
     * @param bool $hasAddress
     * @return ?UserForCurriculumCart
     */
    public function findUserForCurriculumCartByUserId(int $userId, bool $hasTelephone, bool $hasAddress): ?UserForCurriculumCart
    {
        $data = UserDao::select('positionName', 'telephone', 'zipcode', 'country', 'address', 'addressEn')->where('id', $userId)->whereEnabled()->first();
        if (is_null($data)) { return NULL; }

        return new UserForCurriculumCart(
            $userId,
            !is_null($data->positionName),
            $hasTelephone ? $data->telephone : NULL,
            $hasAddress ? $data->country : NULL,
            $hasAddress ? $data->zipcode : NULL,
            $hasAddress ? $data->address : NULL,
            $hasAddress ? $data->addressEn : NULL,
        );
    }

    /**
     * @param int $userId
     * @return ?UserForNotify
     */
    public function findUserForNotifyByUserId(int $userId): ?UserForNotify
    {
        $data = UserDao::select('email', 'displayName')->where('id', $userId)->whereEnabled()->first();
        if (is_null($data)) { return NULL; }

        return new UserForNotify(
            $userId,
            $data->email,
            $data->displayName
        );
    }

    /**
     * @param int $userId
     * @return ?UserForSegment
     */
    public function findUserForSegmentByUserId(int $userId): ?UserForSegment
    {
        $data = UserDao::select('occupationId', 'socialAge')->where('id', $userId)->whereEnabled()->first();
        if (is_null($data)) { return NULL; }

        return new UserForSegment(
            $userId,
            $data->occupationId,
            $data->socialAge
        );
    }

    /**
     * @param UserIndexQuery $query
     * @return UserForIndex[]
     */
    public function findUsersForIndexByQuery(UserIndexQuery $query): array
    {
        $stringKeywordService = new StringKeywordServiceImpl();

        $displayNameKeywords = $stringKeywordService->parse($query->displayName);

        if (is_null($query->email) && !$displayNameKeywords) { return []; }

        $builder = UserDao::select('id', 'email', 'displayName', 'honmuBumonName', 'honmuBushoName')->whereEnabled()->limit($query->perPage)->offset($query->perPage * ($query->page - 1));

        if (!is_null($query->email)) {
            $builder->where('email', $query->email);
        }

        if ($displayNameKeywords) {
            $builder->whereKeywords(['displayName'], $displayNameKeywords);
        }

        return $builder->get()->map(function($data) {
            return new UserForIndex(
                $data->id,
                $data->email,
                $data->displayName,
                $data->honmuBumonName,
                $data->honmuBushoName
            );
        })->toArray();
    }

    /**
     * @param UserSearchQuery $query
     * @return UserForSearch[]
     */
    public function findUsersForSearchByQuery(UserSearchQuery $query): array
    {
        $stringKeywordService = new StringKeywordServiceImpl();

        $displayNameKeywords = $stringKeywordService->parse($query->displayName);

        if (is_null($query->email) && !$displayNameKeywords) { return []; }

        $builder = UserDao::select('id', 'displayName', 'honmuBumonName', 'honmuBushoName')->whereEnabled()->limit($query->limit);

        if (!is_null($query->email)) {
            $builder->where('email', $query->email);
        }

        if ($displayNameKeywords) {
            $builder->whereKeywords(['displayName'], $displayNameKeywords);
        }

        if ($query->exceptUserIds) {
            $builder->whereNotIn('id', $query->exceptUserIds);
        }

        return $builder->get()->map(function($data) {
            return new UserForSearch(
                $data->id,
                $data->displayName,
                $data->honmuBumonName,
                $data->honmuBushoName
            );
        })->toArray();
    }
}
