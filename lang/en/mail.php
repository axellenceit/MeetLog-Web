<?php
return [
    'welcome' => [
        'subject' => 'Welcome to {{organization_name}}',
        'body_html' => '<h2>Welcome to {{organization_name}}!</h2><p>Hello <b>{{name}}</b>,</p><p>we are delighted to have you on board.</p><p>Your login details:</p><p>Username: {{username}}<br>Password: <code>{{password}}</code></p><p>Kind regards,</p><p>The {{organization_name}} team</p>',
    ],
    'deletion' => [
        'subject' => 'Your user account has been deleted',
        'body_html' => '<h2>Your user account has been deleted</h2><p>Hello {{name}},</p><p>Your user account at {{organization_name}} has been deleted and anonymised. Access is no longer possible.</p><p>Kind regards,</p><p>The {{organization_name}} team</p>',
    ],
    'password_reset' => [
        'subject' => 'Your new password at {{organization_name}}',
        'body_html' => '<h2>Password reset</h2><p>Hello <b>{{name}}</b>,</p><p>your password at {{organization_name}} has been reset.</p><p>Your new login details:</p><p>Username: {{username}}<br>Password: <code>{{password}}</code></p><p>Please change your password in your profile after signing in.</p><p>Kind regards,</p><p>The {{organization_name}} team</p>',
    ],
];
