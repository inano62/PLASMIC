<?php

namespace App\Application\Service\Microsoft;

use App\Domain\Object\Microsoft\MicrosoftAuthenticated;

interface MicrosoftGraphService
{
    /**
     * @param string $state
     * @return string
     */
    public function getAuthorizationUrl(string $state): string;

    /**
     * @param string $code
     * @return ?string
     */
    public function authenticateCode(string $code): ?MicrosoftAuthenticated;
}
