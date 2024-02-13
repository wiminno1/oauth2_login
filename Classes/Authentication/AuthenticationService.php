<?php

declare(strict_types = 1);

namespace Wiminno\Oauth2Login\Authentication;

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

use TYPO3\CMS\Core\Authentication\AuthenticationService as AuthService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Wiminno\Oauth2Login\Service\UserService;

/**
 * @author (c) 2024 Kallol Chakraborty <kchakraborty@learntube.de>
 */
class AuthenticationService extends AuthService
{
    protected Context $context;

    protected UserService $userService;

    public function __construct()
    {
        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->userService = GeneralUtility::makeInstance(UserService::class);
    }

    public function getUser(): array | bool
    {
        // Skip everything if user is already logged in
        if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            return parent::getUser();
        }

        // Find User with the Oauth2 Provider Id
        if (!empty($_COOKIE['OAuth2UserId'])) {
            // Remoce Cookie "OAuth2UserId", rely on TYPO3 Cookie only
            setcookie('OAuth2UserId', '', time() - 3600, '/', '', true, true);

            $oauth2UserId = base64_decode($_COOKIE['OAuth2UserId']);
            return $this->userService->findByExternalId($oauth2UserId);
        }

        return parent::getUser();
    }

    public function authUser(array $user): int
    {
        if (empty($user['uid'])) {
            return 100;
        }

        return 200;
    }
}
