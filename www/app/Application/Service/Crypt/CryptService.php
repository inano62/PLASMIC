<?php

namespace App\Application\Service\Crypt;

interface CryptService
{
    /**
     * @param string $value
     * @return string
     */
    public function makeHash(string $value): string;
}
