<?php

namespace App\Application\Exception\Import;

use Exception;

class ImportInvalidException extends Exception
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
