<?php
namespace App\Application\InputData;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use App\Application\Exception\Validation\ValidationFailedException;

abstract class AbstractInputData
{
    /**
     * @abstract
     * @param array? $args
     * @return string[]
     */
    abstract public function validator(array $args): array;

    /**
     * @abstract
     * @param array $input
     * @param array? $args
     * @return void
     */
    abstract public function setUp(array $input, array $args);

    /**
     * @param array $input
     * @param array|null $args
     */
    public function __construct(array $input = [], ...$args)
    {
        $validatorArray = $this->validator($args);
        if ($validatorArray) {
            $validator = \Validator::make($input, $validatorArray);
            if ($validator->fails()) {$this->raiseValidationError($validator);} 
        }
        $this->setUp($input, $args);
    }

    /**
     * @param  mixed  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->{$key};
    }

    /**
     * @access protected
     * @param Illuminate\Validation\Validator $validator
     * @throws ValidationFailedException
     */
    protected function raiseValidationError(Validator $validator)
    {
        throw new ValidationFailedException($validator->errors());
    }

    /*
     * @access protected
     * @throws ValidationFailedException
     */
    protected function abortValidationError()
    {
        throw new ValidationFailedException(new MessageBag());
    }
}
