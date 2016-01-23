<?php
// Routes

$app->get('/{user}/{repo}/{hash}', 'App\API\Measurement:get');
$app->post('/{user}/{repo}/{hash}', 'App\API\Measurement:post');
$app->delete('/{user}/{repo}/{hash}', 'App\API\Measurement:delete');
