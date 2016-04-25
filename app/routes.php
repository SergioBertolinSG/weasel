<?php
// Routes

$app->get('/{user}/{repo}', 'App\API\Measurement:getList');
$app->get('/{user}/{repo}/{hash}', 'App\API\Measurement:get');
$app->post('/{user}/{repo}/{hash}', 'App\API\Measurement:post');
$app->delete('/{user}/{repo}/{hash}', 'App\API\Measurement:delete');
$app->post('/github/webhook', 'App\API\WebHooks:github');
