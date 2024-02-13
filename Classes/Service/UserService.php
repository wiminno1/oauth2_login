<?php

declare(strict_types = 1);

namespace Wiminno\Oauth2Login\Service;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author (c) 2024 Kallol Chakraborty <kchakraborty@learntube.de>
 */
class UserService
{
    protected ConnectionPool $connectionPool;

    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public function addUser(array $externalUser): void
    {
        $this->connectionPool->getConnectionForTable('fe_users')
            ->insert(
                'fe_users',
                [
                    'oauth2_user_id' => $externalUser['id'],
                    'username' => $externalUser['mail'],
                    'password' => password_hash(uniqid(), PASSWORD_ARGON2I),
                    'usergroup' => $externalUser['usergroup'],
                    'first_name' => $externalUser['givenName'],
                    'last_name' => $externalUser['surname'],
                    'email' => $externalUser['mail'],
                    'name' => $externalUser['displayName'],
                    'pid' => $externalUser['pid'],
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME']
                ]
            );
    }

    public function findByEmail(string $email): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

        $result = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($email, \PDO::PARAM_STR))
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            return $row;
        }

        return [];
    }

    public function findByExternalId(string $externalUserId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

        $result = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('oauth2_user_id', $queryBuilder->createNamedParameter($externalUserId, \PDO::PARAM_STR))
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            return $row;
        }

        return [];
    }

    public function updateOauth2Info(int $user, string $oauth2UserId): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

        $queryBuilder
            ->update('fe_users')
            ->set('oauth2_user_id', $oauth2UserId)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($user, \PDO::PARAM_INT)
                )
            )
            ->executeStatement();
    }
}
