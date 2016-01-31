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
- start weasle in dev mode: `php -S 127.0.0.1:8080 -t public public/index.php`
- open the page to trigger the DB setup for the token
- insert a token:

    TOKEN=$(curl -X GET http://127.0.0.1:5984/_uuids -s | cut -d '"' -f 4)
    curl -X PUT http://localhost:5984/token/$TOKEN -H "Content-Type: application/json" -d '{
       "type": "token",
       "user": "MyUserName",
       "permissions": [],
       "expires": null,
       "ip": null,
       "lastUsed": null,
       "user-agent": []
    }'
    echo $TOKEN

- insert a metric:

    curl -i -X POST http://127.0.0.1:8080/username/reponame/abcdefghijkl0123456789 -H "Content-Type: application/json" -H "Authorization: token $TOKEN" -d '{
     "measurement": {
       "queries": {
         "filecache": {
           "SELECT": 1234,
           "UPDATE": 12,
           "INSERT": 3,
           "DELETE": 2
         }
       },
       "performance": [
         {
           "value": 1234.5,
           "unit": "ms",
           "cardinality": 1000,
           "type": "get"
         }
       ]
     },
     "environment": {
       "php": "7.0.2"
     }
   }'

## TODOs

- [ ] what to do
- [ ] next ..