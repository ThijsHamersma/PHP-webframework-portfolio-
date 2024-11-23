<?php

namespace Psr\Http\Server;

use Psr\Http\Message\Request;
use Psr\Http\Message\Response;

interface RequestHandlerInterface{
    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * bron: https://www.php-fig.org/psr/psr-15/
     */
    public function handle(Request $request): Response;
}