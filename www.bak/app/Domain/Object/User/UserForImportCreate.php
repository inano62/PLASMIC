<?php

namespace App\Domain\Object\User;

class UserForImportCreate
{
    /**
     * @param array $data
     * @param ?bool $isUpdatedSocialAge
     * @return void
     */
    public function __construct(
        array $data,
        ?bool $isUpdatedSocialAge
    )
    {
        $this->data = $data;
        $this->isUpdatedSocialAge = $isUpdatedSocialAge;
    }
}
