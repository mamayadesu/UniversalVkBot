<?php

namespace Swoole\Http;

/**
 * @ignore
 */

class Request
{
    public array $server;
    public bool $requestError = false;
    private string $rc;

    public function __construct(array $headers, string $rawcontent, string $name)
    {
        $name1 = explode(':', $name);
        $remote_addr = $name1[0];
        $remote_port = $name1[1];

        $s = array();
        if (!isset($headers[0]))
        {
            $this->requestError = true;
            echo "\nFake Swoole: Bad request. No headers 2\n";
            return;
        }
        $h0 = explode(' ', $headers[0]);
        $s["request_method"] = $h0[0];

        $s["request_uri"] = $h0[1];
        if (strpos("?", $h0[1]) !== false)
        {
            $h01 = explode('?', $h0[1]);
            $s["request_uri"] = $h01[0];
            array_shift($h01);
            $s["query_string"] = implode('?', $h01);
        }
        $s["path_info"] = $s["request_uri"];
        $s["request_time"] = time();
        $s["request_time_float"] = microtime(true);
        $s["server_protocol"] = $h0[2];
        $s["server_port"] = 0;
        $s["remote_port"] = $remote_port;
        $s["remote_addr"] = $remote_addr;
        $s["master_time"] = time();
        $this->server = $s;

        $this->rc = $rawcontent;
    }

    public function rawcontent() : string
    {
        return $this->rc;
    }
}