<?php

namespace App\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TokenCheck implements AuthenticationInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $token
     * @throws \Exception if the authentication failed
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $token)
    {
        // TODO needs to be implemented
        throw new \Exception('could not verify token');
    }
}
