<?php

use Interop\Container\ContainerInterface;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Handler\StreamHandler;

// DIC configuration

$container = $app->getContainer();

// monolog
$container['logger'] = function (ContainerInterface $c) {
    $settings = $c->get('settings');
    $logger = new Logger($settings['logger']['name']);
    $logger->pushProcessor(new UidProcessor());
    $logger->pushHandler(new StreamHandler($settings['logger']['path'], Logger::DEBUG));
    return $logger;
};

// couchdb
$container['couchFactory'] = function (ContainerInterface $c) {
    return function($dbname) {
        return \Doctrine\CouchDB\CouchDBClient::create(['dbname' => urlencode($dbname)]);
    };
};

