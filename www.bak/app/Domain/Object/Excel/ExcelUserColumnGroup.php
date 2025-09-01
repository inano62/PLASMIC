<?php

namespace App\Domain\Object\Excel;

class ExcelUserColumnGroup
{
    public function __construct(
        public readonly int $employeeNumberIndex,
        public readonly int $honmuBumonCodeIndex,
        public readonly int $honmuBumonNameIndex,
        public readonly int $honmuBushoCodeIndex,
        public readonly int $honmuBushoNameIndex,
    )
    {
    }
}
