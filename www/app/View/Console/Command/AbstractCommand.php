<?php

namespace App\View\Console\Command;

use Illuminate\Console\Command;
use App\Adapter\Injector\Injector;

abstract class AbstractCommand extends Command
{
    /**
     * @param mix $inputData
     * @return mix
     */
    protected function handleUseCase($inputData)
    {
        return (new Injector())->handleUseCase($inputData);
    }

    /**
     * @param string $key
     * @return mix
     */
    protected function getImplement(string $key)
    {
        return (new Injector())[$key];
    }
}
