<?php
return [
    'root' => 'Settings',
    'general' => 'General',
    'database' => 'Database',
    'mail' => 'Mail configuration',

    'sections' => [
        'general' => [
            'url' => 'URL',
            'togglePublicMeeting' => 'Public Meetings',
            'file_logo' => 'Logo',
            'logRotation' => 'Log rotation (days)',
        ],
        'database' => [
            'host' => 'Server',
            'port' => 'Port',
            'name' => 'Database name',
            'user' => 'Username',
            'pass' => 'Password',
            'charset' => 'Charset',
        ],
        'mail' => [
            'server' => 'Server',
            'port' => 'Port',
            'usessl' => 'Encryption method (TLS, SSL or none)',
            'user' => 'Username',
            'pass' => 'Password',
            'from' => 'Emails from',
            'toggleEveryCC' => 'Send every email to the sender',
        ],
    ],
];
