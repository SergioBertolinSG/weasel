<?php
$settings = [
    'settings' => [
        // Slim Settings
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => true,

        // monolog settings
        'logger' => [
            'name' => 'app',
            'path' => __DIR__ . '/../log/' . date('Y-m-d') . '.log',
        ],

        // sentry settings (optional)
        // 'sentry' => 'https://PUBLIC:SECRET@sentry.example.org/ID',
    ],
];

$sentryUrl = getenv('SENTRY_URL');
if(!empty($sentryUrl)) {
    $settings['settings']['sentry'] = $sentryUrl;
}

return $settings;
