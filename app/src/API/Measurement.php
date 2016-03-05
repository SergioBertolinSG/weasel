<?php
namespace App\API;

use App\DesignDocuments\MeasurementDesignDocument;
use App\Middleware\Token;
use Doctrine\CouchDB\CouchDBClient;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Container;

class Measurement
{
    /** @var LoggerInterface */
    private $logger;
    /** @var callable */
    private $couchFactory;
    /** @var array */
    private $token;

    public function __construct(Container $c)
    {
        $this->logger = $c->get('logger');
        $this->couchFactory = $c->get('couchFactory');
        $this->token = $c->get('token')->getToken();
    }

    private function setupDB(CouchDBClient $couch)
    {
        // TODO move this to DB creation code
        $name = urldecode($couch->getDatabase());
        if(!in_array($name, $couch->getAllDatabases()))
        {
            $this->logger->info("Set up new database " . $name);
            $couch->createDatabase($name);
            $couch->createDesignDocument('measurement', new MeasurementDesignDocument());
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return \Psr\Http\Message\MessageInterface
     */
    public function getList(Request $request, Response $response, $args)
    {
        $name = $args['user'] . '/' . $args['repo'];
        /** @var CouchDBClient $couch */
        $couch = call_user_func($this->couchFactory, $name);
        $this->setupDB($couch);

        $query = $couch->createViewQuery('measurement', 'by_hash');
        $query->setReduce(false);
        $result = $query->execute();

        $data = [];
        foreach ($result as $row) {
            $data[] = $row['key'];
        }

        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return \Psr\Http\Message\MessageInterface
     */
    public function get(Request $request, Response $response, $args)
    {
        $name = $args['user'] . '/' . $args['repo'];
        /** @var CouchDBClient $couch */
        $couch = call_user_func($this->couchFactory, $name);
        $this->setupDB($couch);

        $query = $couch->createViewQuery('measurement', 'by_hash');
        $query->setReduce(false);
        $query->setKey($args['hash']);
        $result = $query->execute();

        $data = [];
        foreach ($result as $row) {
            unset($row['value']['_id']);
            unset($row['value']['_rev']);
            unset($row['value']['type']);
            unset($row['value']['hash']);
            $data[] = $row['value'];
        }

        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return \Psr\Http\Message\MessageInterface
     */
    public function post(Request $request, Response $response, $args)
    {
        $name = $args['user'] . '/' . $args['repo'];
        /** @var CouchDBClient $couch */
        $couch = call_user_func($this->couchFactory, $name);
        $this->setupDB($couch);

        $data = $request->getParsedBody();

        if(empty($data)) {
            $error = [
                'message' => 'The request must contain a JSON object.'
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }

        $keys = array_keys($data);
        sort($keys);
        if($keys !== ['environment', 'measurement']) {
            $error = [
                'message' => 'The elements "environment" and "measurement" must be set and must be the only elements.'
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }

        $data['type'] = 'measurement';
        $data['created_at'] = (new \DateTime())->format(\DateTime::ISO8601);
        $data['hash'] = $args['hash'];

        $uuids = $couch->getUuids(1);
        $couch->putDocument($data, $uuids[0]);

        // don't send internal attributes
        unset($data['hash'], $data['type']);

        $response->getBody()->write(json_encode($data));
        return $response->withStatus(201)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return \Psr\Http\Message\MessageInterface
     */
    public function delete(Request $request, Response $response, $args)
    {
        $name = $args['user'] . '/' . $args['repo'];
        /** @var CouchDBClient $couch */
        $couch = call_user_func($this->couchFactory, $name);
        $this->setupDB($couch);


        $query = $couch->createViewQuery('measurement', 'by_hash');
        $query->setReduce(false);
        $query->setKey($args['hash']);
        $result = $query->execute();

        foreach ($result as $row) {
            $couch->deleteDocument($row['value']['_id'], $row['value']['_rev']);
        }

        return $response->withStatus(204);
    }
}
