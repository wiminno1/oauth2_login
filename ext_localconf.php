<?php

declare(strict_types = 1);

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

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Wiminno\Oauth2Login\Authentication\AuthenticationService;
use Wiminno\Oauth2Login\Controller\TokenController;

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = true;

ExtensionManagementUtility::addService(
    'oauth2_login',
    'auth',
    AuthenticationService::class,
    [
        'title' => 'User authentication',
        'description' => 'Authentication with username/password.',
        'subtype' => 'getUserFE, authUserFE',
        'available' => true,
        'priority' => 70,
        'quality' => 70,
        'os' => '',
        'exec' => '',
        'className' => AuthenticationService::class
    ]
);

ExtensionManagementUtility::addTypoScriptConstants(
    "@import 'EXT:oauth2_login/Configuration/TypoScript/constants.typoscript'"
);

ExtensionManagementUtility::addTypoScriptSetup(
    "@import 'EXT:oauth2_login/Configuration/TypoScript/setup.typoscript'"
);

ExtensionUtility::configurePlugin(
    'oauth2_login',
    'Pi1',
    [
        TokenController::class => 'authorize'
    ],
    [
        TokenController::class => 'authorize'
    ]
);

ExtensionUtility::configurePlugin(
    'oauth2_login',
    'Pi2',
    [
        TokenController::class => 'verify, handle'
    ],
    [
        TokenController::class => 'verify, handle'
    ]
);
