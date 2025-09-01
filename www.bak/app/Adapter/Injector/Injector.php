<?php

namespace App\Adapter\Injector;

/**
 * いわゆるDIコンテナ
 * 本来は一個一個 依存先を指定するべきだが、
 * 開発速度を重視し、メタブログラミング的手法を用いている
 */

class Injector extends AbstractInjector
{
    /**
     * @access protected
     * @param string $label
     * @return string|object|null
     */
    protected function createClassNameOrObject($label)
    {
        if (substr($label, 0, 24) === 'App\\Application\\UseCase\\') {
            return $label;
        } elseif (substr($label, 0, 11) === 'App\\Domain\\') {
            return str_replace('App\\Domain\\', 'App\\Adapter\\', $label) . 'Impl';
        } elseif (substr($label, 0, 16) === 'App\\Application\\') {
            return str_replace('App\\Application\\', 'App\\Adapter\\', $label) . 'Impl';
        } else {
            return NULL;
        }
    }

    /**
     * @access protected
     * @param string $label
     * @return string
     */
    protected function createUseCaseClassName($label)
    {
        return str_replace('InputData', 'UseCase', $label);
    }

}
