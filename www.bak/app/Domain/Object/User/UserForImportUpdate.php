<?php

namespace App\Domain\Object\User;

class UserForImportUpdate
{
    /**
     * @param ?int $userId
     * @param array $data
     * @param ?bool $isUpdatedSocialAge
     * @param array $previousData
     * @return void
     */
    public function __construct(
        ?int $userId,
        array $data,
        ?bool $isUpdatedSocialAge,
        array $previousData = [],
    )
    {
        $this->userId = $userId;
        $this->data = $data;
        $this->isUpdatedSocialAge = $isUpdatedSocialAge;
        $this->previousData = $previousData;
    }
}
