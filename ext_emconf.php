<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Oauth2 Frontend Login',
    'description' => 'Enables TYPO3 OAuth2 Login for Frontend Users',
    'category' => 'services',
    'author' => 'Kallol Chakraborty',
    'author_email' => 'kchakraborty@learntube.de',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-12.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
