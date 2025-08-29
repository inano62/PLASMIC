<?php

namespace App\Application\UseCase\Import;

use Illuminate\Support\Arr;
use App\Application\Exception\Import\AlreadyException;
use App\Application\Exception\Import\ImportInvalidException;
use App\Application\InputData\Import\ImportExecUserInputData;
use App\Application\Service\Csv\CsvUserService;
use App\Domain\Object\UserImport\UserImportForCreate;
use App\Domain\Reader\ContractTypeReader;
use App\Domain\Reader\JoinWayReader;
use App\Domain\Reader\ImportReader;
use App\Domain\Reader\OccupationReader;
use App\Domain\Reader\PositionReader;
use App\Domain\Reader\RoleReader;
use App\Domain\Reader\UserReader;
use App\Domain\Reader\UserDuplicatedReader;
use App\Domain\Service\User\UserImportService;
use App\Domain\Writer\UserWriter;
use App\Domain\Writer\UserDuplicatedWriter;
use App\Domain\Writer\InvalidUserWriter;

class ImportExecUserUseCase
{
    /**
     * @return void
     */
    public function __construct(
        public readonly CsvUserService $csvUserService,
        public readonly ContractTypeReader $contractTypeReader,
        public readonly JoinWayReader $joinWayReader,
        public readonly ImportReader $importReader,
        public readonly OccupationReader $occupationReader,
        public readonly PositionReader $positionReader,
        public readonly RoleReader $roleReader,
        public readonly UserReader $userReader,
        public readonly UserDuplicatedReader $userDuplicatedReader,
        public readonly UserImportService $userImportService,
        public readonly InvalidUserWriter $invalidUserWriter,
        public readonly UserWriter $userWriter,
        public readonly UserDuplicatedWriter $userDuplicatedWriter,
    )
    {
    }

    /**
     * @param ImportExecUserInputData $inputData
     * @return void
     */
    public function handle(ImportExecUserInputData $inputData)
    {
        $importId = $inputData->importId;
        $time = $inputData->time;
        $way = 7;
        $isStrict = TRUE;

        $content = $this->importReader->findContentByImportId($importId);
        if (is_null($content)) { abort(500); }

        list($rows, $columnGroup) = $this->csvUserService->readForImport($content);

        if (!$rows) { 
            throw new ImportInvalidException(['データが見つかりません。']); 
        }

        if (is_null($columnGroup->employeeCodeIndex)) { 
            throw new ImportInvalidException(['ユーザーIDが見つかりません。']); 
        }
        if (($way && 1) !== 0 && is_null($columnGroup->emailIndex)) {
            throw new ImportInvalidException(['メインメールアドレスが見つかりません。']); 
        }
        if ($isStrict && !$this->userImportService->isValidUserImportColumnGroupIfStrict($columnGroup)) {
            throw new ImportInvalidException(['足りない列があります。']); 
        }

        $shouldDisable = ($way & 4) !== 0;

        $allEmployeeCodes = Arr::pluck($rows, $columnGroup->employeeCodeIndex);
        if (!is_null($columnGroup->previousEmployeeCodeIndex)) { $allEmployeeCodes = array_merge($allEmployeeCodes, array_filter(Arr::pluck($rows, $columnGroup->previousEmployeeCodeIndex))); }
        $employeeCodes = array_values(array_unique($allEmployeeCodes));

        $emails = !is_null($columnGroup->emailIndex) ? array_unique(Arr::pluck($rows, $columnGroup->emailIndex)) : [];
        $contractTypeNames = !is_null($columnGroup->contractTypeIndex) ? array_unique(Arr::pluck($rows, $columnGroup->contractTypeIndex)) : [];
        $joinWayNames = !is_null($columnGroup->joinWayIndex) ? array_unique(Arr::pluck($rows, $columnGroup->joinWayIndex)) : [];
        $occupationNames = !is_null($columnGroup->occupationIndex) ? array_unique(Arr::pluck($rows, $columnGroup->occupationIndex)) : [];
        $positionNames = !is_null($columnGroup->positionIndex) ? array_unique(Arr::pluck($rows, $columnGroup->positionIndex)) : [];
        $roleNames = !is_null($columnGroup->roleIndex) ? array_unique(Arr::pluck($rows, $columnGroup->roleIndex)) : [];

        $contractTypeNameToId = $this->contractTypeReader->findSearchToContractTypeIdBySearches($contractTypeNames);
        if (count($contractTypeNameToId) !== count($contractTypeNames)) {
            throw new ImportInvalidException(['従業員区分が見つかりません。']); 
        }
        $joinWayNameToId = $this->joinWayReader->findSearchToJoinWayIdBySearches($joinWayNames);
        if (count($joinWayNameToId) !== count($joinWayNames)) {
            throw new ImportInvalidException(['プロパー／キャリア入社が見つかりません。']); 
        }
        $occupationNameToId = $this->occupationReader->findSearchToOccupationIdBySearches($occupationNames);
        if (count($occupationNameToId) !== count($occupationNames)) {
            throw new ImportInvalidException(['職種名称が見つかりません。']); 
        }
        $positionNameToId = $this->positionReader->findSearchToPositionIdBySearches($positionNames);
        if (count($positionNameToId) !== count($positionNames)) {
            throw new ImportInvalidException(['役職名称が見つかりません。']); 
        }
        $roleNameToId = $this->roleReader->findSearchToRoleIdBySearches($roleNames);
        if (count($roleNameToId) !== count($roleNames)) {
            throw new ImportInvalidException(['ロールが見つかりません。']); 
        }

        $rows = $this->userImportService->replaceNameToIdInRows($rows, $columnGroup, $contractTypeNameToId, $joinWayNameToId, $occupationNameToId, $positionNameToId, $roleNameToId);

        $employeeCodeToUserId = $this->userReader->findEmployeeCodeToUserIdByEmployeeCodes($employeeCodes);
        $emailToUserId = $this->userReader->findEmailToUserIdByEmails($emails);

        $disabledUserIds = $shouldDisable ? $this->userReader->findUserIdsByExceptUserIds(array_values($employeeCodeToUserId)) : [];

        list (
            $createUsers,
            $maybeUpdateUsers,
            $maybeDuplicatedUsers,
            $invalidUsers,
        ) = $this->userImportService->parse($rows, $columnGroup, $way, $employeeCodeToUserId, $emailToUserId);

        $updateUserIds = Arr::pluck($maybeUpdateUsers, 'userId');
        $updateUserIdToData = $this->userReader->findUserIdToDataForImportByUserIds($updateUserIds);

        $updateUsers = [];
        $enabledUserIds = [];

        foreach ($maybeUpdateUsers as $maybeUpdateUser) {
            $userId = $maybeUpdateUser->userId;
            $data = isset($updateUserIdToData[$userId]) ? $updateUserIdToData[$userId] : [];
            $updateUser = $this->userImportService->calculateUserForImportUpdate($maybeUpdateUser, $data);
            if (!is_null($updateUser)) {
                $updateUsers[] = $updateUser;
            } else {
                $enabledUserIds[] = $userId;
            }
        }

        $createDuplicatedUsers = [];
        $updateDuplicatedUsers = [];

        foreach ($maybeDuplicatedUsers as $dublicatedUser) {
            $userDuplicatedId = $this->userDuplicatedReader->findUserDuplicatedIdByEmployeeCodeEmail($dublicatedUser->employeeCode, $dublicatedUser->email);
            if (is_null($userDuplicatedId)) {
                $createDuplicatedUsers[] = $dublicatedUser;
            } else {
                $updateDuplicatedUsers[] = $this->userImportService->calcualteUserDuplicatedForImportUpdate($userDuplicatedId, $dublicatedUser->data);
            }
        }

        \DB::beginTransaction();

        $this->userWriter->updateStatusDisabledByUserIds($disabledUserIds);
        $this->userWriter->updateStatusEnabledByUserIds($enabledUserIds);
        $this->userWriter->importCreateBulk($createUsers, $importId, $time);
        $this->userWriter->importUpdateBulk($updateUsers, $importId, $time);
        $this->invalidUserWriter->importBulk($invalidUsers, $importId);
        $this->userDuplicatedWriter->importCreateBulk($createDuplicatedUsers);
        $this->userDuplicatedWriter->importUpdateBulk($updateDuplicatedUsers);

        // UserDuplicate に関する情報のコピー

        $duplicatedEmployeeCodes = $this->userDuplicatedReader->findUntreatedEmployeeCodes();
        $duplicatedUserIds = $this->userReader->findUserIdsByEmployeeCodes($duplicatedEmployeeCodes);
        $duplicatedUserIdToData = $this->userReader->findUserIdToDataForImportByUserIds($duplicatedUserIds);

        $createDuplicatedUsers = [];
        $updateDuplicatedUsers = [];

        foreach ($duplicatedUserIds as $userId) {
            $data = isset($duplicatedUserIdToData[$userId]) ? $duplicatedUserIdToData[$userId] : [];
            $userDuplicatedId = $this->userDuplicatedReader->findUserDuplicatedIdByEmployeeCodeEmail($data['employeeCode'], $data['email']);
            if (is_null($userDuplicatedId)) {
                $createDuplicatedUsers[] = $this->userImportService->calcualteUserDuplicatedForImportCreate($data);
            } else {
                $updateDuplicatedUsers[] = $this->userImportService->calcualteUserDuplicatedForImportUpdate($userDuplicatedId, $data);
            }
        }

        $this->userWriter->updateStatusDuplicatedByUserIds($duplicatedUserIds);
        $this->userDuplicatedWriter->importCreateBulk($createDuplicatedUsers);
        $this->userDuplicatedWriter->importUpdateBulk($updateDuplicatedUsers);

        \DB::commit();
    }
}
