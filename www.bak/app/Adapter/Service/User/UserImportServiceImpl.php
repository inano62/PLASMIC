<?php

namespace App\Adapter\Service\User;

use Illuminate\Support\Arr;
use App\Domain\Service\User\UserImportService;
use App\Domain\Object\User\UserImportColumnGroup;
use App\Domain\Object\User\UserForImportCreate;
use App\Domain\Object\User\UserForImportUpdate;
use App\Domain\Object\UserDuplicated\UserDuplicatedForImportCreate;
use App\Domain\Object\UserDuplicated\UserDuplicatedForImportUpdate;
use App\Domain\Object\InvalidUser\InvalidUserForImport;

class UserImportServiceImpl implements UserImportService
{
    /**
     * @param UserImportColumnGroup $columnGroup
     * @return bool
     */
    public function isValidUserImportColumnGroupIfStrict(UserImportColumnGroup $columnGroup): bool
    {
        foreach ($columnGroup as $key => $value) {
            if (is_null($value)) { return FALSE; }
        }
        return TRUE;
    }

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
    public function replaceNameToIdInRows(array $rows, UserImportColumnGroup $columnGroup, array $contractTypeNameToId, array $joinWayNameToId, array $occupationNameToId, array $positionNameToId, array $roleNameToId): array
    {
        foreach (array_keys($rows) as $i) {
            if (!is_null($columnGroup->contractTypeIndex)) {
                $rows[$i][$columnGroup->contractTypeIndex] = $contractTypeNameToId[$rows[$i][$columnGroup->contractTypeIndex]];
            }
            if (!is_null($columnGroup->joinWayIndex)) {
                $rows[$i][$columnGroup->joinWayIndex] = $joinWayNameToId[$rows[$i][$columnGroup->joinWayIndex]];
            }
            if (!is_null($columnGroup->occupationIndex)) {
                $rows[$i][$columnGroup->occupationIndex] = $occupationNameToId[$rows[$i][$columnGroup->occupationIndex]];
            }
            if (!is_null($columnGroup->positionIndex)) {
                $rows[$i][$columnGroup->positionIndex] = $positionNameToId[$rows[$i][$columnGroup->positionIndex]];
            }
            if (!is_null($columnGroup->roleIndex)) {
                $rows[$i][$columnGroup->roleIndex] = $roleNameToId[$rows[$i][$columnGroup->roleIndex]];
            }
        }

        return $rows;
    }

    /**
     * @param string[][] $rows
     * @param UserImportColumnGroup $columnGroup
     * @param int $way
     * @param int[] $employeeCodeToUserId
     * @param int[] $emailToUserId
     * @return array
     */
    public function parse(array $rows, UserImportColumnGroup $columnGroup, int $way, array $employeeCodeToUserId, array $emailToUserId): array
    {
        $shouldCreate = ($way && 1) !== 0;
        $shouldUpdate = ($way && 2) !== 0;

        $creatableUsers = [];
        $updatableUsers = [];
        $forbiddenEmployeeCodes = [];
        
        do {
            $unprocessedRows = [];

            foreach ($rows as $row) {
                $employeeCode = $row[$columnGroup->employeeCodeIndex];
                $email = !is_null($columnGroup->emailIndex) ? $row[$columnGroup->emailIndex] : NULL;
                $employeeCodeUserId = !empty($employeeCodeToUserId[$employeeCode]) ? $employeeCodeToUserId[$employeeCode] : NULL;
                $emailUserId = !empty($emailToUserId[$email]) ? $emailToUserId[$email] : NULL;
                $previousEmployeeCode = !is_null($columnGroup->previousEmployeeCodeIndex) ? $row[$columnGroup->previousEmployeeCodeIndex] : NULL;
                $previousEmployeeCodeUserId = !empty($employeeCodeToUserId[$previousEmployeeCode]) ? $employeeCodeToUserId[$previousEmployeeCode] : NULL;
                $displayName = !is_null($columnGroup->displayNameIndex) ? $row[$columnGroup->displayNameIndex] : NULL;

                if ($shouldCreate && is_null($employeeCodeUserId) && is_null($emailUserId) && !is_null($employeeCode) && !is_null($email) && !is_null($displayName) && !in_array($employeeCode, $forbiddenEmployeeCodes, TRUE)) {
                    // 問題なく作成
                    $creatableUsers[] = $this->generateUserForImportCreate($row, $columnGroup);
                    $employeeCodeToUserId[$employeeCode] = -1;
                    $emailToUserId[$email] = -1;
                } else if ($shouldUpdate && !is_null($employeeCodeUserId) && !is_null($email) && is_null($emailUserId) && $employeeCodeUserId > 0) {
                    // 問題なく更新(メールアドレス変更あり)
                    $updatableUsers[] = $this->generateUserForImportUpdate($employeeCodeUserId, $row, $columnGroup);
                    if (($index = array_search($employeeCodeUserId, $emailToUserId, TRUE)) !== FALSE) { unset($emailToUserId[$email]); }
                    $emailToUserId[$email] = $employeeCodeUserId;
                } else if ($shouldUpdate && !is_null($employeeCodeUserId) && (is_null($columnGroup->emailIndex) || $emailUserId === $employeeCodeUserId) && $employeeCodeUserId > 0) {
                    // 問題なく更新(メール変更なし)
                    $updatableUsers[] = $this->generateUserForImportUpdate($employeeCodeUserId, $row, $columnGroup);
                } else if ($shouldUpdate && is_null($employeeCodeUserId) && !is_null($previousEmployeeCodeUserId) && $previousEmployeeCodeUserId === $emailUserId && $previousEmployeeCodeUserId > 0) {
                    // 問題なく更新(従業員番号変更)
                    $updatableUsers[] = $this->generateUserForImportUpdate($previousEmployeeCodeUserId, $row, $columnGroup);
                    unset($employeeCodeToUserId[$previousEmployeeCode]);
                    $employeeCodeToUserId[$employeeCode] = $previousEmployeeCodeUserId;
                    $forbiddenEmployeeCodes[] = $previousEmployeeCode;
                } else {
                    $unprocessedRows[] = $row;
                }
            }

            $isChanged = count($unprocessedRows) !== count($rows);
            $rows = $unprocessedRows;

        } while ($isChanged);

        $employeeCodeSet = [];
        $emailSet = [];
        foreach (array_merge(Arr::pluck($creatableUsers, 'data'), Arr::pluck($updatableUsers, 'data')) as $data) {
            if (isset($data['employeeCode'])) { $employeeCodeSet[$data['employeeCode']] = TRUE; }
            if (isset($data['email'])) { $emailSet[$data['email']] = TRUE; }
        }

        $dublicatedUsers = [];
        $invalidUsers = [];

        foreach ($rows as $row) {

            $employeeCode = $row[$columnGroup->employeeCodeIndex];
            $email = !is_null($columnGroup->emailIndex) ? $row[$columnGroup->emailIndex] : NULL;
            $employeeCodeUserId = !empty($employeeCodeToUserId[$employeeCode]) ? $employeeCodeToUserId[$employeeCode] : NULL;
            $emailUserId = !empty($emailToUserId[$email]) ? $emailToUserId[$email] : NULL;
            $displayName = !is_null($columnGroup->displayNameIndex) ? $row[$columnGroup->displayNameIndex] : NULL;

            if ($shouldCreate && is_null($employeeCodeUserId) && !is_null($emailUserId) && !is_null($employeeCode) && !is_null($displayName) && !isset($employeeCodeSet[$employeeCode]) && !isset($emailSet[$email])) {
                // 社員番号は新規だが メール被り
                $dublicatedUsers[] = $this->generateUserDuplicatedForImportCreate($row, $columnGroup);
                $employeeCodeSet[$employeeCode] = TRUE; $emailSet[$email] = TRUE;
            } else if ($shouldUpdate && !is_null($employeeCodeUserId) && !is_null($emailUserId) && $employeeCodeUserId !== $emailUserId && !is_null($displayName) && !isset($employeeCodeSet[$employeeCode]) && !isset($emailSet[$email])) {
                // 社員番号は
                $dublicatedUsers[] = $this->generateUserDuplicatedForImportCreate($row, $columnGroup);
                $employeeCodeSet[$employeeCode] = TRUE; $emailSet[$email] = TRUE;
            } else {
                $invalidUsers[] = $this->generateInvalidUserForImport($row, $columnGroup);
            }
        }

        return [
            $creatableUsers,
            $updatableUsers,
            $dublicatedUsers,
            $invalidUsers,
        ];
    }

    /**
     * @param UserForImportUpdate $user
     * @param array $data
     * @return ?UserForImportUpdate
     */
    public function calculateUserForImportUpdate(UserForImportUpdate $user, array $data): ?UserForImportUpdate
    {
        $changedData = [];

        foreach ($user->data as $key => $value) {

            $previousValue = isset($data[$key]) ? $data[$key] : NULL;
            if ($previousValue !== $value) {
                $changedData[$key] = $value;
            }
        }

        if (!$changedData) { return NULL; }

        return new UserForImportUpdate(
            $user->userId,
            $changedData,
            $user->isUpdatedSocialAge,
            $data,
        );
    }

    /**
     * @param array $data
     * @return UserDuplicatedForImportCreate
     */
    public function calcualteUserDuplicatedForImportCreate(array $data): UserDuplicatedForImportCreate
    {
        return new UserDuplicatedForImportCreate(
            $data['employeeCode'],
            $data['email'],
            $data
        );
    }

    /**
     * @param int $userDuplicatedId
     * @param array $data
     * @return UserDuplicatedForImportUpdate
     */
    public function calcualteUserDuplicatedForImportUpdate(int $userDuplicatedId, array $data): UserDuplicatedForImportUpdate
    {
        return new UserDuplicatedForImportUpdate(
            $userDuplicatedId,
            $data
        );
    }

    /**
     * @access private
     * @param array $row
     * @param UserImportColumnGroup $columnGroup
     * @return UserForImportCreate
     */
    private function generateUserForImportCreate(array $row, UserImportColumnGroup $columnGroup): UserForImportCreate
    {
        $update = $this->generateUpdateArray($row, $columnGroup);
        return new UserForImportCreate(
            $update,
            isset($update['socialAge'])
        );
    }

    /**
     * @param int $userId
     * @param array $row
     * @param UserImportColumnGroup $columnGroup
     * @return UserImportColumn
     */
    private function generateUserForImportUpdate(int $userId, array $row, UserImportColumnGroup $columnGroup): UserForImportUpdate
    {
        $update = $this->generateUpdateArray($row, $columnGroup);
        return new UserForImportUpdate(
            $userId,
            $update,
            isset($update['socialAge'])
        );
    }

    /**
     * @param array $row
     * @param UserImportColumnGroup $columnGroup
     * @return UserImportColumn
     */
    private function generateInvalidUserForImport(array $row, UserImportColumnGroup $columnGroup): InvalidUserForImport
    {
        return new InvalidUserForImport(
            $this->generateUpdateArray($row, $columnGroup)
        );
    }

    /**
     * @param array $row
     * @param UserImportColumnGroup $columnGroup
     * @return UserDuplicatedForImportCreate
     */
    private function generateUserDuplicatedForImportCreate(array $row, UserImportColumnGroup $columnGroup): UserDuplicatedForImportCreate
    {
        $update = $this->generateUpdateArray($row, $columnGroup);
        return new UserDuplicatedForImportCreate(
            $update['employeeCode'],
            $update['email'],
            $update
        );
    }

    /**
     * @param array $row
     * @param UserImportColumnGroup $columnGroup
     * @return array
     */
    private function generateUpdateArray(array $row, UserImportColumnGroup $columnGroup): array
    {
        $update = [];

        if (!is_null($columnGroup->employeeCodeIndex)) {
            $update['employeeCode'] = $row[$columnGroup->employeeCodeIndex];
        }
        if (!is_null($columnGroup->displayNameIndex)) {
            $update['displayName'] = $row[$columnGroup->displayNameIndex];
        }
        if (!is_null($columnGroup->surnameKanjiIndex)) {
            $update['surnameKanji'] = $row[$columnGroup->surnameKanjiIndex];
        }
        if (!is_null($columnGroup->forenameKanjiIndex)) {
            $update['forenameKanji'] = $row[$columnGroup->forenameKanjiIndex];
        }
        if (!is_null($columnGroup->surnameKatakanaIndex)) {
            $update['surnameKatakana'] = $row[$columnGroup->surnameKatakanaIndex];
        }
        if (!is_null($columnGroup->forenameKatakanaIndex)) {
            $update['forenameKatakana'] = $row[$columnGroup->forenameKatakanaIndex];
        }
        if (!is_null($columnGroup->surnameAlphabetIndex)) {
            $update['surnameAlphabet'] = $row[$columnGroup->surnameAlphabetIndex];
        }
        if (!is_null($columnGroup->forenameAlphabetIndex)) {
            $update['forenameAlphabet'] = $row[$columnGroup->forenameAlphabetIndex];
        }
        if (!is_null($columnGroup->emailIndex)) {
            $update['email'] = $row[$columnGroup->emailIndex];
        }
        if (!is_null($columnGroup->bumonGroupCodeIndex)) {
            $update['bumonGroupCode'] = $row[$columnGroup->bumonGroupCodeIndex];
        }
        if (!is_null($columnGroup->bumonGroupNameIndex)) {
            $update['bumonGroupName'] = $row[$columnGroup->bumonGroupNameIndex];
        }
        if (!is_null($columnGroup->bumonCodeIndex)) {
            $update['bumonCode'] = $row[$columnGroup->bumonCodeIndex];
        }
        if (!is_null($columnGroup->bumonNameIndex)) {
            $update['bumonName'] = $row[$columnGroup->bumonNameIndex];
        }
        if (!is_null($columnGroup->bushoCodeIndex)) {
            $update['bushoCode'] = $row[$columnGroup->bushoCodeIndex];
        }
        if (!is_null($columnGroup->bushoNameIndex)) {
            $update['bushoName'] = $row[$columnGroup->bushoNameIndex];
        }
        if (!is_null($columnGroup->positionIndex)) {
            $update['positionId'] = $row[$columnGroup->positionIndex];
        }
        if (!is_null($columnGroup->contractTypeIndex)) {
            $update['contractTypeId'] = $row[$columnGroup->contractTypeIndex];
        }
        if (!is_null($columnGroup->honmuCompanyNameIndex)) {
            $update['honmuCompanyName'] = $row[$columnGroup->honmuCompanyNameIndex];
        }
        if (!is_null($columnGroup->honmuBumonGroupCodeIndex)) {
            $update['honmuBumonGroupCode'] = $row[$columnGroup->honmuBumonGroupCodeIndex];
        }
        if (!is_null($columnGroup->honmuBumonGroupNameIndex)) {
            $update['honmuBumonGroupName'] = $row[$columnGroup->honmuBumonGroupNameIndex];
        }
        if (!is_null($columnGroup->honmuBumonCodeIndex)) {
            $update['honmuBumonCode'] = $row[$columnGroup->honmuBumonCodeIndex];
        }
        if (!is_null($columnGroup->honmuBumonNameIndex)) {
            $update['honmuBumonName'] = $row[$columnGroup->honmuBumonNameIndex];
        }
        if (!is_null($columnGroup->honmuBushoCodeIndex)) {
            $update['honmuBushoCode'] = $row[$columnGroup->honmuBushoCodeIndex];
        }
        if (!is_null($columnGroup->honmuBushoNameIndex)) {
            $update['honmuBushoName'] = $row[$columnGroup->honmuBushoNameIndex];
        }
        if (!is_null($columnGroup->joinWayIndex)) {
            $update['joinWayId'] = $row[$columnGroup->joinWayIndex];
        }
        if (!is_null($columnGroup->occupationIndex)) {
            $update['occupationId'] = $row[$columnGroup->occupationIndex];
        }
        if (!is_null($columnGroup->roleIndex)) {
            $update['roleId'] = $row[$columnGroup->roleIndex];
        }
        if (!is_null($columnGroup->socialAgeIndex)) {
            $update['socialAge'] = $row[$columnGroup->socialAgeIndex];
        }
        if (!is_null($columnGroup->genderIndex)) {
            $update['gender'] = $row[$columnGroup->genderIndex];
        }
        return $update;
    }
}
