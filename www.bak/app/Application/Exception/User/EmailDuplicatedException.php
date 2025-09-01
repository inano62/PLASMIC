<?php

namespace App\Application\Exception\User;

use Exception;

class EmailDuplicatedException extends Exception
{
    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
}
