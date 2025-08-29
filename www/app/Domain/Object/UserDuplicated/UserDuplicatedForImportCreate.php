<?php

namespace App\Domain\Object\UserDuplicated;

class UserDuplicatedForImportCreate
{
    /**
     * @param int $employeeCode
     * @param string $email
     * @param array $data
     * @return void
     */
    public function __construct(
        public readonly int $employeeCode,
        public readonly string $email,
        public readonly array $data
    )
    {
    }
}
