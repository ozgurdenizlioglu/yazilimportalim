<?php
declare(strict_types=1);

return [
    'app_env'  => $_ENV['APP_ENV'] ?? 'prod',
    'debug'    => (($_ENV['APP_DEBUG'] ?? 'false') === 'true'),
    'base_url' => rtrim($_ENV['APP_URL'] ?? '', '/'),
];
