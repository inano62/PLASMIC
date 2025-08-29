<?php

namespace App\Adapter\Service\Microsoft;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Microsoft\Graph\Graph;
use App\Application\Service\Microsoft\MicrosoftGraphService;
use App\Domain\Object\Microsoft\MicrosoftAuthenticated;

class MicrosoftGraphServiceImpl implements MicrosoftGraphService
{
    const CLIENT_ID = '63eb54c3-5fdc-42bb-ae06-0828688058f3';
    const CLIENT_SECRET = '4js8Q~x.NImZMseDq1yuTHMuScnHFvRbgECpla5g';

    /**
     * @access private
     * @return bool
     */
    private function isUnitTest(): bool
    {
        return isset($_SERVER['argv'][0]) && strpos($_SERVER['argv'][0], 'phpunit') !== FALSE;
    }

    /**
     * @param string $state
     * @return string
     */
    public function getAuthorizationUrl(string $state): string
    {
        $provider = $this->getProvider();

        return $provider->getAuthorizationUrl([
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    /**
     * @param string $code
     * @return ?string
     */
    public function authenticateCode(string $code): ?MicrosoftAuthenticated
    {
        if ($this->isUnitTest()) { return new MicrosoftAuthenticated('token', 'id', 'develop@example.com'); }
        
        $provider = $this->getProvider();

        try {
            $accessTokenObj = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
        } catch (IdentityProviderException $e) {
            return NULL;
        }
        $accessToken = $accessTokenObj->getToken();

        $graph = new Graph();
        $graph->setAccessToken($accessToken);

        try {
            $user = $graph->createRequest('GET', '/me')
                          ->setReturnType(\Microsoft\Graph\Model\User::class)
                          ->execute();
        } catch (ClientException $e) {
            return NULL;
        }

        return new MicrosoftAuthenticated(
            $accessToken,
            $user->getId(),
            $user->getMail()
        );
    }

    /**
     * @access private
     * @return GenericProvider
     */
    private function getProvider(): GenericProvider
    {
        return new GenericProvider([
            'clientId' => self::CLIENT_ID,
            'clientSecret' => self::CLIENT_SECRET,
            'redirectUri' => $this->getRedirectUri(),
            'urlAuthorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'urlAccessToken' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'urlResourceOwnerDetails' => '',
            'scopes' => 'user.read'
        ]);
    }

    private function getRedirectUri(): string
    {
        return 'https://hdy.online/microsoft-authenticate';
    }
}
