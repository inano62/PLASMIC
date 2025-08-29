<?php

namespace App\Domain\Object\UserDuplicated;

class UserDuplicatedForImportUpdate
{
    /**
     * @param int $userDuplicatedId
     * @param array $data
     * @return void
     */
    public function __construct(
        int $userDuplicatedId,
        array $data
    )
    {
        $this->userDuplicatedId = $userDuplicatedId;
        $this->data = $data;
    }
}
