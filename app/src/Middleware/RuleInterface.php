<?php

/*
 * This file is part of Slim JSON Web Token Authentication middleware
 *
 * Copyright (c) 2015 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-jwt-auth
 *
 */

namespace App\Middleware;

use Psr\Http\Message\RequestInterface;

interface RuleInterface
{
    /**
     * @param RequestInterface $request
     * @return boolean - return true if authentication is needed, false otherwise
     */
    public function __invoke(RequestInterface $request);
}
