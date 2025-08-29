<?php

namespace App\Domain\Object\User;

class UserImportColumnGroup
{
    /**
     * @return void
     */
    public function __construct(
        public readonly ?int $employeeCodeIndex,
        public readonly ?int $previousEmployeeCodeIndex,
        public readonly ?int $displayNameIndex,
        public readonly ?int $surnameKanjiIndex,
        public readonly ?int $forenameKanjiIndex,
        public readonly ?int $surnameKatakanaIndex,
        public readonly ?int $forenameKatakanaIndex,
        public readonly ?int $surnameAlphabetIndex,
        public readonly ?int $forenameAlphabetIndex,
        public readonly ?int $emailIndex,
        public readonly ?int $bumonGroupCodeIndex,
        public readonly ?int $bumonGroupNameIndex,
        public readonly ?int $bumonCodeIndex,
        public readonly ?int $bumonNameIndex,
        public readonly ?int $bushoCodeIndex,
        public readonly ?int $bushoNameIndex,
        public readonly ?int $positionIndex,
        public readonly ?int $contractTypeIndex,
        public readonly ?int $honmuCompanyNameIndex,
        public readonly ?int $honmuBumonGroupCodeIndex,
        public readonly ?int $honmuBumonGroupNameIndex,
        public readonly ?int $honmuBumonCodeIndex,
        public readonly ?int $honmuBumonNameIndex,
        public readonly ?int $honmuBushoCodeIndex,
        public readonly ?int $honmuBushoNameIndex,
        public readonly ?int $joinWayIndex,
        public readonly ?int $occupationIndex,
        public readonly ?int $roleIndex,
        public readonly ?int $socialAgeIndex,
        public readonly ?int $genderIndex,
    )
    {
    }
}
