<?php

namespace App\Adapter\Reader;

use App\Adapter\Service\String\StringKeywordServiceImpl;
use App\Domain\Constant\UserDuplicatedConstant;
use App\Domain\Reader\UserDuplicatedReader;
use App\Infrastructure\Dao\UserDuplicatedDao;

class UserDuplicatedReaderImpl implements UserDuplicatedReader
{
    /**
     * @param int $employeeCode
     * @param string $code
     * @return bool
     */
    public function findUserDuplicatedIdByEmployeeCodeEmail(int $employeeCode, string $email): ?int
    {
        return UserDuplicatedDao::where('employeeCode', $employeeCode)->where('email', $email)->value('id');
    }

    /**
     *
     * @return int[]
     */
    public function findUntreatedEmployeeCodes(): array
    {
        return UserDuplicatedDao::where('status', UserDuplicatedConstant::STATUS_UNTREATED)->groupBy('employeeCode')->pluck('employeeCode')->toArray();
    }

    /**
     * @param int $userDuplicatedId
     * @return ?array
     */
    public function findDataForImportByUserDuplicatedId(int $userDuplicatedId): ?array
    {
        $data = UserDuplicatedDao::select(
            'email',
            'displayName',
            'employeeCode',
            'surnameKanji',
            'forenameKanji',
            'surnameKatakana',
            'forenameKatakana',
            'surnameAlphabet',
            'forenameAlphabet',
            'bumonGroupCode',
            'bumonGroupName',
            'bumonCode',
            'bumonName',
            'bushoCode',
            'bushoName',
            'positionName',
            'contractType',
            'honmuCompanyName',
            'honmuBumonGroupCode',
            'honmuBumonGroupName',
            'honmuBumonCode',
            'honmuBumonName',
            'honmuBushoCode',
            'honmuBushoName',
            'joinWay',
            'occupation',
            'role',
            'socialAge',
        )->where('id', $userDuplicatedId)->where('status', UserDuplicatedConstant::STATUS_UNTREATED)->first();

        if (is_null($data)) { return NULL; }

        $data = $data->toArray();

        return $data;
    }
}
