<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use App\Application\InputData\Me\MeUserInputData;
use App\Infrastructure\Dao\UserDao;
use App\Infrastructure\Dao\UserTokenDao;

class AuthTest extends AbstractTestCase
{
    const USER_ID = 1;

    /**
     * @return void
     */
    public function testAuth()
    {
        $time = strtotime('2022-07-07');

        $email = UserDao::where('id', self::USER_ID)->value('email');
        $employeeCode = UserDao::where('id', self::USER_ID)->value('employeeCode');
        $password = 'password';
        $displayName = UserDao::where('id', self::USER_ID)->value('displayName');

        $inputData = new MeUserInputData($outputData->id, $time);
        $outputData = $this->handleUseCase($inputData);
        $this->assertSame($outputData->displayName, $displayName);
    }
}
