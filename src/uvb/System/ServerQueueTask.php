<?php

namespace uvb\System;

use HttpServer\Request;
use HttpServer\Response;
use HttpServer\Server;

/**
 * @ignore
 */
final class ServerQueueTask
{
    public Server $server;
    public Request $request;
    public Response $response;

    public function __construct(Server $server, Request $request, Response $response)
    {
        $this->server = $server;
        $this->request = $request;
        $this->response = $response;
    }
}