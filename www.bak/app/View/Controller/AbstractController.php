<?php

namespace App\View\Controller;

use Illuminate\Routing\Controller;
use App\Adapter\Injector\Injector;

abstract class AbstractController extends Controller
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

    /**
     * @return void
     */
    protected function closeConnection()
    {
        ob_start();

        echo 'OK';

        header('Connection: close');
        header('Content-Length: '.ob_get_length());
        ob_end_flush(); 
        flush();
    }
}
