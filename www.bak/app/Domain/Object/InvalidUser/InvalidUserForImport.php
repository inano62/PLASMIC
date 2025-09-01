<?php

namespace App\Domain\Object\InvalidUser;

class InvalidUserForImport
{
    /**
     * @param array $update
     * @return void
     */
    public function __construct(
        array $update,
    )
    {
        $this->update = $update;
    }
}
