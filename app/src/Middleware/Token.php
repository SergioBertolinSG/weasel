<?php
/**
 * Created by PhpStorm.
 * User: morrisjobke
 * Date: 24.01.16
 * Time: 22:19
 */

namespace App\Middleware;


class Token
{
    private $token;

    public function setToken($token) {
        $this->token = $token;
    }

    public function getToken() {
        return $this->token;
    }
}