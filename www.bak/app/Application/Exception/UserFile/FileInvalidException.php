<?php

namespace App\Application\Exception\UserFile;

use Exception;

class FileInvalidException extends Exception
{
    /**
     * @param array $errors
     * @return void
     */
    public function __construct(array $errors)
    {
        parent::__construct();

        $this->errors = $errors;
    }
}
