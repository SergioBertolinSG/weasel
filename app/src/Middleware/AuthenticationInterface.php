<?php

namespace App\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface AuthenticationInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $token
     * @throws \Exception if the authentication failed
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $token);
}
