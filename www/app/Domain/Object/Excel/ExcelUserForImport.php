<?php

namespace App\Domain\Object\Excel;

class ExcelUserForImport
{
    public function __construct(
        public readonly int $employeeNumber,
        public readonly int $honmuBumonCode,
        public readonly string $honmuBumonName,
        public readonly int $honmuBushoCode,
        public readonly string $honmuBushoName,
    )
    {
    }
}
