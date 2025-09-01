<?php

namespace App\Application\UseCase\Microsoft;

use App\Application\Exception\Microsoft\MicrosoftEmail404Exception;
use App\Application\InputData\Microsoft\MicrosoftCallbackInputData;
use App\Application\OutputData\Microsoft\MicrosoftCallbackOutputData;
use App\Application\Service\Microsoft\MicrosoftGraphService;
use App\Domain\Constant\AuthConstant;

class MicrosoftCallbackUseCase
{
    /**
     * @param MicrosoftGraphService $microsoftGraphService
     * @return void
     */
    public function __construct(
        public readonly MicrosoftGraphService $microsoftGraphService,
    )
    {
    }

    /**
     * @param MicrosoftCallbackInputData $inputData
     * @return MicrosoftCallbackOutputData
     */
    public function handle(MicrosoftCallbackInputData $inputData): MicrosoftCallbackOutputData
    {
        $code = $inputData->code;

        $microsoftAuthenticated = $this->microsoftGraphService->authenticateCode($code);
        if (is_null($microsoftAuthenticated)) { abort(404); }

        if (!in_array($microsoftAuthenticated->email, AuthConstant::ALLOWED_EMAILS, TRUE)) { throw new MicrosoftEmail404Exception($microsoftAuthenticated->email); }

        return new MicrosoftCallbackOutputData(
            $microsoftAuthenticated->email
        );
    }
}
