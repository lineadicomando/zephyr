<?php

return [
    'delete_grace_hours' => (int) env('SCOPE_DELETE_GRACE_HOURS', 24),
    'console_bypass_commands' => [
        'migrate',
        'migrate:*',
        'db:seed',
        'db:wipe',
    ],
];
