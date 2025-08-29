<?php

namespace App\Domain\Reader;

interface UserDuplicatedReader
{
    /**
     * @param int $employeeCode
     * @param string $code
     * @return bool
     */
    public function findUserDuplicatedIdByEmployeeCodeEmail(int $employeeCode, string $email): ?int;

    /**
     *
     * @return int[]
     */
    public function findUntreatedEmployeeCodes(): array;

    /**
     * @param int $userDuplicatedId
     * @return ?array
     */
    public function findDataForImportByUserDuplicatedId(int $userDuplicatedId): ?array;
}
