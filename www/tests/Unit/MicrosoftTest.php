<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use App\Application\Exception\Microsoft\MicrosoftEmail404Exception;
use App\Application\InputData\Microsoft\MicrosoftCallbackInputData;

class MicrosoftTest extends AbstractTestCase
{
    const USER_ID = 1;

    /**
     * @return void
     */
    public function testAuth()
    {
        $time = time();

        $code = 'codecode';
        
        $inputData = new MicrosoftCallbackInputData([
            'code' => $code,
        ]);
        $this->assertThrowException(function() use($inputData) {
            $this->handleUseCase($inputData);
        }, MicrosoftEmail404Exception::class);
    }
}
