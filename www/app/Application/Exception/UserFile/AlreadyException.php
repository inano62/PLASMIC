<?php

namespace App\Application\Exception\UserFile;

use Exception;

class AlreadyException extends Exception
{
    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
}
