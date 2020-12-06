<?php

namespace Swoole\Http;

/**
 * @ignore
 */

class Server
{
    private string $address;
    private int $port;
    private array $registeredEvents = array();
    private bool $shutdownWasCalled = false;
    private $socket = null;

    public function __construct(string $addr, int $port)
    {
        $this->address = $addr;
        $this->port = $port;
        $this->on("start", function (Server $server) { });
        $this->on("shutdown", function (Server $server) { });
        $this->on("request", function (Request $req, Response $resp) { });
    }

    public function on(string $eventName, callable $callback) : void
    {
        $this->registeredEvents[$eventName] = $callback;
    }

    public function start() : void
    {
        $this->socket = stream_socket_server("tcp://" . $this->address . ":" . $this->port, $errno, $errstr);

        if (!$this->socket)
        {
            die("\nSWOOLE FAKE ERROR (" . $errno . "): " . $errstr . "\n");
        }

        $this->registeredEvents["start"]($this);
        $sourceData = $headerName = $headerValue = $firstHeader = $buffer = $buffer1 = "";
        $headers = [];
        $parsedHeaders = array();
        $header1 = [];
        $headersI = -1;
        $firstHeader1 = [];
        $bufferBroken = false;
        while ($this->socket != null && $connect = stream_socket_accept($this->socket, -1))
        {
            $bufferBroken = false;
            $sourceData = "";
            $headers = [];
            $parsedHeaders = array();
            $header1 = [];
            $headerName = "";
            $headerValue = "";
            $headersI = -1;
            $name = stream_socket_get_name($connect, true);
            //while ($buffer1 = fgets($connect))
            while ($buffer = rtrim(fgets($connect)))
            {
                if ($buffer == false)
                {
                    $bufferBroken = true;
                    break;
                }
                //$buffer = rtrim($buffer1);
                $headersI++;
                $headers[$headersI] = $buffer;
                if ($headersI == 0)
                {
                    $firstHeader = $headers[0];
                    $firstHeader1 = explode(' ', $firstHeader);
                    if ($firstHeader1[count($firstHeader1) - 1] != "HTTP/1.0" && $firstHeader1[count($firstHeader1) - 1] != "HTTP/1.1")
                    {
                        echo "\nFake Swoole: Not HTTP-request\n";
                        $this->debug("nothttp_" . $name, $sourceData);
                        fclose($connect);
                        $bufferBroken = true;
                        break;
                    }
                }
                $sourceData .= "|H|" . $buffer . "\n";
            }
            if ($bufferBroken)
            {
                echo "\nFake Swoole: Buffer broken\n";
                $this->debug("bufferbroken_" . $name, $sourceData);
                fclose($connect);
                continue;
            }
            if (count($headers) == 0)
            {
                echo "\nFake Swoole: Bad request. No headers 1\n";
                fclose($connect);
                continue;
            }
            foreach ($headers as $header)
            {
                $header1 = explode(": ", $header);
                if (count($header1) < 2)
                {
                    continue;
                }
                $headerName = $header1[0];
                array_shift($header1);
                $headerValue = implode(' ', $header1);
                $parsedHeaders[$headerName] = $headerValue;
            }
            $body = "";
            //if ($meta["unread_bytes"] > 0)
            if (isset($parsedHeaders["Content-Length"]) && intval($parsedHeaders["Content-Length"]) > 0)
            {
                //$body = fread($connect, $meta["unread_bytes"]);
                stream_set_timeout($connect, 10);
                $contentLength = intval($parsedHeaders["Content-Length"]);
                //echo "\nREADING " . $contentLength . " BYTES\n";
                $body = fread($connect, intval($parsedHeaders["Content-Length"]));
                //echo "\nGOT " . strlen($body) . " BYTES\n";
                $sourceData .= "|B|" . $body;
            }
            $meta = stream_get_meta_data($connect);
            if ($meta["timed_out"])
            {
                echo "\nFake Swoole: " . $name . " timed out\n";
                fclose($connect);
                $this->debug("timedout_" . $name, $sourceData);
                continue;
            }
            $body = urldecode($body);
            $request = new Request($headers, $body, $name);
            $request->server["server_port"] = $this->port;

            $response = new Response($connect);
            if ($request->requestError)
            {
                $response->end("");
                $this->debug("requesterr_" . $name, $sourceData);
                continue;
            }
            if (isset($parsedHeaders["Expect"]) && strtolower($parsedHeaders["Expect"]) == "100-continue" && strlen($body) < $parsedHeaders["Content-Length"])
            {
                $response->status(100);
                $response->end("");
                $this->debug("continue_" . $name, $sourceData);
                continue;
            }
            $this->debug($name, $sourceData);
            $response->header("Content-Type", "text/html");
            $response->header("Connection", "close");

            $this->registeredEvents["request"]($request, $response);
            if (!$response->closed())
            {
                $response->status(500);
                $response->end("");
                echo "\nFake Swoole: connection wasn't closed\n";
            }
            if ($this->shutdownWasCalled)
            {
                fclose($this->socket);
                $this->socket = null;
                echo "\nFake Swoole is shutting down\n";
                $this->registeredEvents["shutdown"]($this);
            }
        }
    }

    private function debug(string $name, string $sourceData) : void
    {
        return;
        $ip = "127.0.0.1";
        $name = str_replace(":", "-", $name);
        if ($ip == substr($name, 0, strlen($ip)))
        {
            return;
        }
        $path = "C:\\Users\\Semyon\\Documents\\PHPStorm\\UVB Server\\swoole_dumps\\";
        $i = 1;
        $filename = $path . $name . "_" . $i . ".txt";
        while (file_exists($filename))
        {
            $i++;
            $filename = $path . $name . "_" . $i . ".txt";
        }
        echo "\nDUMP SAVED AS " . $filename . "\n";
        $f = fopen($filename, "w");
        fwrite($f, $sourceData);
        fclose($f);
    }

    public function close() : void
    {
        // does nothing here
    }

    public function shutdown() : void
    {
        $this->shutdownWasCalled = true;
    }
}