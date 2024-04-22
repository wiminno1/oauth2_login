<?php
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types = 1);

namespace Wiminno\Oauth2Login\Controller;

/* * *************************************************************
 *
 *  Copyright notice
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TheNetworg\OAuth2\Client\Provider\Azure;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use Wiminno\Oauth2Login\Service\UserService;

/**
 * @author (c) 2024 Kallol Chakraborty <kchakraborty@learntube.de>
 */
class TokenController extends ActionController
{
    protected Azure $provider;

    protected Context $context;

    protected UserService $userService;

    protected array $extConfig;

    protected array $queryParams;

    public function __construct()
    {
        $configObj = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->extConfig = $configObj->get('oauth2_login');

        foreach ($this->extConfig as $configKey=>$configValue) {
            if (in_array($configKey, ['tenantId'])) {
                continue;
            }

            if (empty($configValue)) {
                throw new RuntimeException('Incomplete setup. Missing configuration: '. $configKey);
            }
        }

        $configuration = [
            'clientId' => trim($this->extConfig['clientId']),
            'clientSecret' => trim($this->extConfig['clientSecret']),
            'redirectUri' => trim($this->extConfig['redirectUri']),
            'scopes' => ['openid'],
            'defaultEndPointVersion' => '2.0'
        ];

        $tenantId = trim($this->extConfig['tenantId']);
        if (!empty($tenantId)) {
            $configuration['tenant'] = $tenantId;
        }

        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->provider = GeneralUtility::makeInstance(Azure::class, $configuration);
        $this->userService = GeneralUtility::makeInstance(UserService::class);
    }

    public function processRequest(RequestInterface $request): ResponseInterface
    {
        $this->queryParams = $request->getQueryParams();
        return parent::processRequest($request);
    }

    public function authorizeAction(): ResponseInterface
    {
        // Skip everything if user is already logged in
        if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            return new RedirectResponse(trim($this->extConfig['feUserRedirectUri']), 302);
        }

        $baseGraphUri = $this->provider->getRootMicrosoftGraphUri(null);
        $this->provider->scope = 'openid profile email offline_access ' . $baseGraphUri . '/User.Read';
        $authorizationUrl = $this->provider->getAuthorizationUrl(['scope' => $this->provider->scope]);

        $state = base64_encode($this->provider->getState());
        setcookie('OAuth2State', $state, time() + 300, '/', '', true, true);

        return new RedirectResponse($authorizationUrl, 302);
    }

    public function verifyAction(): ResponseInterface | RuntimeException
    {
        // Skip everything if user is already logged in
        if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            return new RedirectResponse(trim($this->extConfig['feUserRedirectUri']), 302);
        }

        if (array_key_exists('code', $this->queryParams) && array_key_exists('state', $this->queryParams) && isset($_COOKIE['OAuth2State'])) {
            // Fetch values from QueryString
            $code = $this->queryParams['code'];
            $state = $this->queryParams['state'];

            // Validate State
            if ($state == base64_decode($_COOKIE['OAuth2State'])) {
                setcookie('OAuth2State', '', time() - 3600, '/', '', true, true);

                // Get Access Token
                $token = $this->provider->getAccessToken('authorization_code', [
                    'scope' => $this->provider->scope,
                    'code' => $code,
                ]);

                // Validate Token and Get User
                if (!empty($token->getToken())) {
                    $me = $this->provider->get($this->provider->getRootMicrosoftGraphUri($token) . '/v1.0/me', $token);
                    setcookie('OAuth2UserId', base64_encode($me['id']), time() + 300, '/', '', true, true);

                    // Validate user E-mail for further processing
                    if (!filter_var($me['mail'], FILTER_VALIDATE_EMAIL)) {
                        throw new RuntimeException('E-mail field is not configured for the user: '.$me['displayName']);
                    }

                    // Check if user with the detected E-mail already exists in TYPO3 system
                    $user = $this->userService->findByEmail($me['mail']);
                    if (empty($user)) {
                        $newUser = [
                            'id' => $me['id'],
                            'displayName' => $me['displayName'],
                            'surname' => $me['surname'],
                            'givenName' => $me['givenName'],
                            'mail' => $me['mail'],
                            'pid' => $this->extConfig['feUserStoragePid'],
                            'usergroup' => $this->extConfig['feUserGroups'],
                        ];
                        // Add a new user
                        $this->userService->addUser($newUser);
                    } else {
                        if (empty($user['oauth2_user_id'])) {
                            // Update the existing user
                            $this->userService->updateOauth2Info($user['uid'], $me['id']);
                        }
                    }

                    return new RedirectResponse(trim($this->extConfig['feUserRedirectUri']), 302);
                }

                // Something went wrong with the token
                throw new RuntimeException('Invalid Token');
            } else {
                // Something went wrong with the State
                throw new RuntimeException('Invalid State');
            }
        }

        // Bad Request
        if (array_key_exists('error_description', $this->queryParams)) {
            throw new RuntimeException(trim($this->queryParams['error_description']));
        } else {
            throw new RuntimeException('Bad request - Unknown error');
        }
    }
}
