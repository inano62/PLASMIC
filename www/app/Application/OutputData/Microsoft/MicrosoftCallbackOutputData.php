<?php

namespace App\Application\OutputData\Microsoft;

use Illuminate\Http\RedirectResponse;
use App\Application\OutputData\AbstractOutputData;
use App\Domain\Object\User\Authenticated;
use App\Domain\Constant\AuthConstant;

class MicrosoftCallbackOutputData extends AbstractOutputData
{
    /**
     * @param string $email
     * @return void
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function writeSession()
    {
        // セッションを設定
        session([
            AuthConstant::SESSION_EMAIL_KEY => $this->email,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        return redirect('//visual.hdy.online');
    }
}
