<?php

namespace App\Domain\Object\Microsoft;

class MicrosoftAuthenticated
{
    /**
     * @param string $accessToken
     * @param string $uid
     * @param string $email
     * @return void
     */
    public function __construct(
        string $accessToken,
        string $uid,
        string $email
    )
    {
        $this->accessToken = $accessToken;
        $this->uid = $uid;
        $this->email = $email;
    }
}
