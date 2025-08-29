<?php

namespace App\Adapter\Service\Crypt;

use App\Application\Service\Crypt\CryptService;

class CryptServiceImpl implements CryptService
{
    /**
     * @param string $value
     * @return string
     */
    public function makeHash(string $value): string
    {
        return md5(md5($value));
    }
}
