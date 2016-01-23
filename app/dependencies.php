<?php

use Interop\Container\ContainerInterface;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Handler\StreamHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// DIC configuration

$container = $app->getContainer();

// monolog
$container['logger'] = function (ContainerInterface $c) {
    $settings = $c->get('settings');
    $logger = new Logger($settings['logger']['name']);
    $logger->pushProcessor(new UidProcessor());
    $logger->pushHandler(new StreamHandler($settings['logger']['path'], Logger::DEBUG));

    $sentryDSN = $settings->get('sentry');
    if(!empty($sentryDSN)) {
        $sentryHandler = new \Monolog\Handler\RavenHandler(new Raven_Client($sentryDSN), Logger::WARNING);
        $sentryHandler->setFormatter(new Monolog\Formatter\LineFormatter("%message%\n"));
        $logger->pushHandler($sentryHandler);
    }

    return $logger;
};

$container['errorHandler'] = function (ContainerInterface $c) {
    return function (RequestInterface $request, ResponseInterface $response, \Exception $exception) use ($c) {

        // Standard exception data
        $error = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'tracestring' => $exception->getTraceAsString()
        ];

        /** @var \Psr\Log\LoggerInterface[] $c */
        $c['logger']->error($exception->getMessage(), $error);

        // TODO
        $production = false;

        if($production) {
            $error['message'] = 'There was an internal error';
            unset($error['file'], $error['line'], $error['trace']);
        }

        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    };
};

// couchdb
$container['couchFactory'] = function (ContainerInterface $c) {
    return function($dbname) {
        return \Doctrine\CouchDB\CouchDBClient::create(['dbname' => urlencode($dbname)]);
    };
};

