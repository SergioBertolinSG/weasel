<?php
// Application middleware

$settings = $container->get('settings');
$app->add(new \App\Middleware\TokenAuthentication(
    new \App\Middleware\TokenCheck($app, $container->get('logger'), $container->get('token'), $container->get('couchFactory')),
        $container->get('logger'))
);