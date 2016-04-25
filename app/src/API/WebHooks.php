<?php
namespace App\API;

use App\DesignDocuments\MeasurementDesignDocument;
use App\Middleware\Token;
use Doctrine\CouchDB\CouchDBClient;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Container;

class WebHooks
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
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return \Psr\Http\Message\MessageInterface
     */
    public function github(Request $request, Response $response)
    {
        /** @var CouchDBClient $couch */
        $couch = call_user_func($this->couchFactory, 'webhooks');
        $this->setupDB($couch);

        $body = $request->getParsedBody();

        if(empty($body)) {
            $error = [
                'message' => 'The request must contain a JSON object.'
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }

        $request->getHeaders();

        $data = [
            'headers' => $request->getHeaders(),
            'body' => $body
        ];

        $uuids = $couch->getUuids(1);
        $couch->putDocument($data, $uuids[0]);

        return $response->withStatus(201);
    }
}
