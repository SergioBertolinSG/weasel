<?php

namespace App\Middleware;

use App\DesignDocuments\TokenDesignDocument;
use Doctrine\CouchDB\CouchDBClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\App;

class TokenCheck implements AuthenticationInterface
{
    /** @var LoggerInterface */
    private $logger;
    /** @var CouchDBClient */
    private $couch;
    /** @var App */
    private $app;
    /** @var Token */
    private $token;


    public function __construct(App $app, LoggerInterface $logger, Token $token, callable $couchFactory)
    {
        $this->app = $app;
        $this->logger = $logger;
        $this->token = $token;
        $this->couch = $couchFactory('token');

        // TODO move this to DB creation code
        if (!in_array('token', $this->couch->getAllDatabases())) {
            $this->logger->info('Set up new database "token"');
            $this->couch->createDatabase('token');
            $this->couch->createDesignDocument('token', new TokenDesignDocument());
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $token
     * @throws \Exception if the authentication failed
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $token)
    {
        if (!is_string($token) || $token === '') {
            throw new \Exception('token is empty');
        }

        $tokenEntry = $this->couch->findDocument($token);

        if ($tokenEntry->status === 200) {
            $tokenDocument = $tokenEntry->body;
            $this->checkToken($tokenDocument);

            /* append user agent to list */
            $userAgent = $request->getHeader('User-Agent');
            if (isset($userAgent[0])) {
                $userAgent = $userAgent[0];
                if (false === array_search($userAgent, $tokenDocument['user-agent'])) {
                    $tokenDocument['user-agent'][] = $userAgent;
                }
            }

            $tokenDocument['ip'] = $_SERVER['REMOTE_ADDR'];
            $tokenDocument['lastUsed'] = (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::ISO8601);

            $this->couch->putDocument($tokenDocument, $tokenDocument['_id']);

            $this->token->setToken($tokenDocument);
            return;
        }

        throw new \Exception('could not verify token - response code: ' . $tokenEntry->status);
    }

    private function checkToken($token)
    {
        if (!isset($token['type']) || $token['type'] !== 'token') {
            throw new \Exception('not a token');
        }

        if (isset($token['expires']) && !is_null($token['expires'])) {
            $expireDate = \DateTime::createFromFormat(\DateTime::ISO8601, $token['expires']);
            $now = new \DateTime();
            if($expireDate < $now) {
                throw new \Exception('token expired');
            }
        }
    }
}
