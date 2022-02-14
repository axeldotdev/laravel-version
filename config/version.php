<?php

return [
    'changelog' => [
        'enabled' => true,
        'mode' => 'group', // simple
    ],

    'platform' => [
        'enabled' => false,
        'name' => null, // bitbucket, github, gitlab
    ],

    'commits' => [
        'hidden' => [
            'Update changelog and app version',
            'Update app version',
            'fix',
            'wip',
            'fix conflicts',
        ],
    ],
];
