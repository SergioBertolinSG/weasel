<?php

/*
 * inspired by Slim JSON Web Token Authentication middleware - https://github.com/tuupola/slim-jwt-auth
 */

namespace App\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * by default this middleware authenticates:
 *  - every request ('/' as default path)
 *  - every method except OPTIONS request (if no rule is specified)
 *
 * The token is fetched from the HTTP header "Authorization: token {actualTokenHere}" or from the 'access_token' query
 * parameter (in that order - if header is specified the query parameter is ignored).
 */
class TokenAuthentication
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var callable */
    protected $callback;

    /**
     * TokenAuthentication constructor.
     * @param AuthenticationInterface $callback
     * @param LoggerInterface $logger
     * @param array $options:
     *     - callback - check token implementation
     *     - rules (optional) - array of RuleInterface implementations
     *     - path (optional) - path to limit access to
     */
    public function __construct(AuthenticationInterface $callback, LoggerInterface $logger, array $options = [])
    {
        $this->logger = $logger;
        $this->callback = $callback;
        $this->rules = new \SplStack;

        /* If nothing was passed in options add default rules. */
        if (!isset($options['rules'])) {
            $this->rules->push(new RequestMethodRule([
                'passthrough' => ['OPTIONS']
            ]));
        } else {
            foreach($options['rules'] as $rule) {
                $this->rules->push($rule);
            }
        }

        /* If path was given in easy mode add rule for it. */
        $this->rules->push(new RequestPathRule([
            'path' => isset($options['path']) ? $options['path'] : '',
            'passthrough' => ['/github/webhook'],
        ]));
    }

    /**
     * Call the middleware
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate($request)) {
            return $next($request, $response);
        }

        try {
            $token = $this->fetchToken($request);
            /* call the callback with the token to authenticate */
            $callback = $this->callback;
            $callback($request, $response, $token);
        } catch (\Exception $e) {
            $this->logger->info('authentication failed: ' . $e->getMessage());
            return $response->withStatus(401);
        }

        /* Everything ok, call next middleware and return. */
        return $next($request, $response);
    }

    /**
     * Check if middleware should authenticate
     *
     * @return boolean True if middleware should authenticate.
     */
    public function shouldAuthenticate(RequestInterface $request)
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            /** @var RuleInterface $callable */
            if (false === $callable($request)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Fetch the access token
     *
     * @return string access token
     * @throws \Exception when no token can be found
     */
    public function fetchToken(RequestInterface $request)
    {
        /* check Authorization header for 'token abcdefghi'  */
        $header = $request->getHeader('Authorization');
        $header = isset($header[0]) ? $header[0] : '';
        if (preg_match('/token\s+(.*)$/i', $header, $matches)) {
            $this->logger->debug('Authorization header used');
            return $matches[1];
        }

        /* check query parameter for 'access_token' */
        $query = $request->getUri()->getQuery();
        foreach (explode('&', $query) as $chunk) {
            $param = explode('=', $chunk);
            if (urldecode($param[0]) === 'access_token') {
                return urldecode($param[1]);
            }
        }

        /* if everything fails log and throw exception */
        $message = 'token not found';
        throw new \Exception($message);
    }
}
