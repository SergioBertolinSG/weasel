<?php
namespace App\API;

use App\DesignDocuments\MeasurementDesignDocument;
use App\Middleware\Token;
use Doctrine\CouchDB\CouchDBClient;
use GuzzleHttp\Exception\ClientException;
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

        $body = $request->getParsedBody();

        if(empty($body)) {
            $error = [
                'message' => 'The request must contain a JSON object.'
            ];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }

        if($request->getHeader('X-GitHub-Event') !== ['pull_request']) {
            return $response->withStatus(200);
        }

        /** @var CouchDBClient $couch */
        $couch = call_user_func($this->couchFactory, 'webhooks');
        $this->setupDB($couch);

        $data = [
            'type' => 'queue',
            'created_at' => (new \DateTime())->format(\DateTime::ISO8601),
            'status' => 'pending',
            'repo' => $body['repository']['full_name'],
            'pr_url' => $body['pull_request']['html_url'],
            'api_url' => $body['pull_request']['url'],
            'statuses_url' => $body['pull_request']['statuses_url'],
            'action' => $body['action'],
            'head_sha' => $body['pull_request']['head']['sha'],
            'base_sha' => $body['pull_request']['base']['sha'],
            'request' => [
                'headers' => $request->getHeaders(),
                'body' => $body
            ]
        ];

        $apiKey = getenv('GITHUB_API_KEY');
        if(!empty($apiKey)){
            $statusData = [
                'state' => 'pending',
                'target_url' => 'https://google.com',
                'description' => 'Performance run is queued',
                'context' => 'weasel/performance'
            ];
            $client = new \GuzzleHttp\Client();
            try {
                $res = $client->request('POST', $body['pull_request']['statuses_url'], [
                    'headers' => ['Authorization' => 'token ' . $apiKey],
                    'body' => json_encode($statusData),
                ]);
                if($res->getStatusCode() === 201) {
                    $statusBody = json_decode($res->getBody()->getContents(), true);
                    $data['status_url'] = $statusBody['url'];
                } else {
                    // TODO queue the status update to re-deliver
                    $this->logger->info('delivering status: return code different from 201 returned');
                }
            } catch (ClientException $e) {
                // TODO queue the status update to re-deliver
                $this->logger->info($e->getMessage());
            }
        }

        $uuids = $couch->getUuids(1);
        $couch->putDocument($data, $uuids[0]);

        return $response->withStatus(201);
    }
}
