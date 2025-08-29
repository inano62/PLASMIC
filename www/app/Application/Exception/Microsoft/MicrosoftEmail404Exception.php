<?php

namespace App\Application\Exception\Microsoft;

use Exception;

class MicrosoftEmail404Exception extends Exception
{
    /**
     * @param string $email
     * @return void
     */
    public function __construct(string $email)
    {
        parent::__construct($email);
        
        $this->email = $email;
    }
}
