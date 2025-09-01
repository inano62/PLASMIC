<?php

namespace App\Domain\Constant;

class AuthConstant
{
    const SESSION_EMAIL_KEY = 'email';

    const ALLOWED_EMAILS = [
        // 開発用
        'nagai@zedteam.onmicrosoft.com',
        // 博報堂様
        'ayako.kodama@hakuhodo.co.jp',
        'natsuko.takemoto@hakuhodo.co.jp',
    ];
}
