# Weasel: Performance testing

TODO: Tellin three sentences what weasel is, motivation, what problem it solves ..

## Install

- clone this repo
- install CouchDB with `brew install couchdb` (Mac) or `apt-get install couchdb` (Debian)
- install Composer dependencies `composer install`
- check CouchDB with `curl http://127.0.0.1:5984/` (should answer some JSON)
- browse CouchDB with http://127.0.0.1:5984/_util/

## Start

- start CouchDB with `couchdb` (or run as service, see install output)
- start Weazle in dev mode: `php -S 127.0.0.1:8080 -t public public/index.php`
- call http://127.0.0.1:8080/bla/fasel/abc in browser
- TODO what next? curl??

## TODOs

- [ ] what to do
- [ ] next ..

## Tech

- Micro-Framework: [**Slim**](http://www.slimframework.com)
- Logger: [Monolog](https://github.com/Seldaek/monolog) (Sends your logs to files, sockets, inboxes, databases and various web services)
- Persistance: [CouchDB](https://couchdb.apache.org) with [Doctrine](http://www.doctrine-project.org)