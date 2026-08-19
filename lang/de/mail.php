<?php
return [
    'welcome' => [
        'subject' => 'Willkommen bei {{organization_name}}',
        'body_html' => '<h2>Willkommen bei {{organization_name}}!</h2><p>Hallo <b>{{name}}</b>,</p><p>wir freuen uns, dich an Bord zu haben.</p><p>Deine Zugangsdaten:</p><p>Benutzername: {{username}}<br>Passwort: <code>{{password}}</code></p><p>Beste Grüße,</p><p>Das {{organization_name}}-Team</p>',
    ],
    'deletion' => [
        'subject' => 'Dein Benutzerkonto wurde gelöscht',
        'body_html' => '<h2>Dein Benutzerkonto wurde gelöscht</h2><p>Hallo {{name}},</p><p>Dein Benutzerkonto bei {{organization_name}} wurde gelöscht und anonymisiert. Ein Zugriff ist ab jetzt nicht mehr möglich.</p><p>Beste Grüße,</p><p>Das {{organization_name}}-Team</p>',
    ],
];
