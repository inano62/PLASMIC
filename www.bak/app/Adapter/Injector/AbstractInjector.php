<?php

namespace App\Adapter\Injector;

use ArrayAccess;

abstract class AbstractInjector implements ArrayAccess
{

    /**
     * @abstract
     * @access protected
     * @param string $label
     * @return string|object|null
     */
    abstract protected function createClassNameOrObject($label);

    /**
     * @abstract
     * @access protected
     * @param string $label
     * @return string
     */
    abstract protected function createUseCaseClassName($label);

    /**
     * @var array
     * @access private
     */
    private $_cache = [];

    /**
     * @access private
     * @param string $label
     * @return object
     */
    private function createInstance(string $label)
    {
        $classNameOrObject = $this->createClassNameOrObject($label);

        if (is_object($classNameOrObject)) {
            return $classNameOrObject;
        } elseif (is_null($classNameOrObject)) {
            return $this->raiseNotFoundError($label);
        } else {
            return $this->createInstanceByClassName($classNameOrObject);
        }
    }

    /**
     * @access private
     * @param string $className
     * @return $object
     */
    private function createInstanceByClassName(string $className)
    {
        $reflection = new \ReflectionClass($className);

        if ($reflection->hasMethod('__construct')) {
            $parameters = $reflection->getConstructor()->getParameters();
            $args = array_reduce($parameters, function ($args, $parameter) {
                if ($reflectionClass = $parameter->getType()) {
                    $className = $reflectionClass->getName();
                    $args[$parameter->getName()] = $this[$className];
                }

                return $args;
            }, []);
        } else {
            $args = [];
        }
        return $reflection->newInstanceArgs($args);
    }

    /**
     * @access public
     * @param \App\Application\InputData\AbstractInputData $inputData
     * @return \App\Application\OutputData\AbstractOutputData $outputData
     */
    public function handleUseCase($inputData)
    {
        $useCaseClassName = $this->createUseCaseClassName(get_class($inputData));

        if (is_null($useCaseClassName)) {
            return $this->raiseNotFoundError(get_class($inputData));
        } else {
            return $this->__get($useCaseClassName)->handle($inputData);
        }
    }

    /**
     * @access private
     * @param string $label
     * @return void
     */
    private function raiseNotFoundError(string $label)
    {
        throw new \Exception("NotFoundInInjector($label)");
    }

    /**
     * @final
     * @param string $label
     * @return object
     */
    public final function __get(string $label)
    {
        if (!isset($this->_cache[$label])) {
            $this->_cache[$label] = $this->createInstance($label);
        }
        return $this->_cache[$label];
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        return !is_null($this->createClassName($key));
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->__get($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->__set($key, $value);
    }

    /**
     * @param mixed $key
     * @return void
     */
    public function offsetUnset(mixed $key): void
    {
        unset($this->_cache[$key]);
    }
}
