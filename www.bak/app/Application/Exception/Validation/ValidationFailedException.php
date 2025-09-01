<?php

namespace App\Application\Exception\Validation;

use Exception;
use Illuminate\Support\MessageBag;

class ValidationFailedException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param MessageBag $errorBag
     * @return void
     */
    public function __construct(MessageBag $errorBag)
    {
        parent::__construct($errorBag->toJson());

        $this->errorBag = $errorBag;
    }
}
