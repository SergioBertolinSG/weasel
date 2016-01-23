<?php
// Routes

$app->get('/{user}/{repo}/{hash}', 'App\API\Metrics:get');
$app->post('/{user}/{repo}/{hash}', 'App\API\Metrics:post');
