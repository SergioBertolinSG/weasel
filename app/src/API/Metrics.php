<?php
namespace App\API;

use App\API\DesignDocuments\MetricDesignDocument;
use Doctrine\CouchDB\CouchDBClient;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Container;

class Metrics
{
    /** @var LoggerInterface */
    private $logger;
    /** @var callable */
    private $couchFactory;

    public function __construct(Container $c)
    {
        $this->logger = $c->get('logger');
        $this->couchFactory = $c->get('couchFactory');
    }

    private function setupDB(CouchDBClient $couch)
    {
        // TODO move this to DB creation code
        $name = urldecode($couch->getDatabase());
        if(!in_array($name, $couch->getAllDatabases()))
        {
            $this->logger->info("Set up new database " . $name);
            $couch->createDatabase($name);
            $couch->createDesignDocument('metrics', new MetricDesignDocument());
        }
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

        $query = $couch->createViewQuery('metrics', 'by_hash');
        $query->setReduce(false);
        $query->setKey($args['hash']);
        $result = $query->execute();

        $data = [];
        foreach ($result as $row) {
            $data[] = $row;
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
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

        $keys = array_keys($data);
        sort($keys);
        if($keys !== ['environment', 'metrics']) {
            $error = [
                'message' => 'The elements "environment" and "metrics" must be set and must be the only elements.'
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(422)
                ->withHeader(
                'Content-Type',
                $response->getBody()->isWritable() ? '1' : '0'
            );
        }

        $data['type'] = 'metric';
        $data['created_at'] = (new \DateTime())->format(\DateTime::ISO8601);
        $data['hash'] = $args['hash'];

        $uuids = $couch->getUuids(1);
        $couch->putDocument($data, $uuids[0]);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }
}
