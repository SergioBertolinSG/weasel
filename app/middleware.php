<?php
// Application middleware

$settings = $container->get('settings');
$app->add(new \App\Middleware\TokenAuthentication(
    new \App\Middleware\TokenCheck(), $container->get('logger'))
);