<?php
return [
    'root' => 'Einstellungen',
    'general' => 'Allgemein',
    'database' => 'Datenbank',
    'mail' => 'Mailkonfiguration',

    'sections' => [
        'general' => [
            'url' => 'URL',
            'togglePublicMeeting' => 'Öffentlichte Meetings',
            'file_logo' => 'Logo',
            'logRotation' => 'Protokollrotation (Tage)',
        ],
        'database' => [
            'host' => 'Server',
            'port' => 'Port',
            'name' => 'Datenbankname',
            'user' => 'Benutzername',
            'pass' => 'Passwort',
            'charset' => 'Charset',
        ],
        'mail' => [
            'server' => 'Server',
            'port' => 'Port',
            'usessl' => 'Verschlüsselungsmethode (TLS, SSL oder keine)',
            'user' => 'Benutzername',
            'pass' => 'Passwort',
            'from' => 'E-Mails von',
            'toggleEveryCC' => 'Jede E-Mail an den Absender senden',
        ],
    ],
];
