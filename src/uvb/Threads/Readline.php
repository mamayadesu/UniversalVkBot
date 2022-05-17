<?php

namespace uvb\Threads;

use Application\Application;
use Threading\Thread;
use IO\Console;

/**
 * @ignore
 */

class Readline extends Thread
{
    private string $ip;
    private int $port;

    public function Threaded(array $args) : void
    {
        $this->ip = $args[0];
        $this->port = intval($args[1]);
        sleep(3);
        $data = array
        (
            "first" => true,
            "key" => "",
            "cmd" => "",
            "pid" => getmypid()
        );
        $result = $this->Send($this->ip, $this->port, $data);
        if ($result == "fail")
        {
            exit;
        }
        $data["key"] = $result;
        $data["first"] = false;
        while (true)
        {
            if (!self::IsParentStillRunning())
            {
                exit;
            }
            $input = Console::ReadLine();
            $data["cmd"] = $input;
            $result = $this->Send($this->ip, $this->port, $data);
            if ($result == "fail" || $result == "Wrong URI")
            {
                if (!self::IsParentStillRunning())
                {
                    exit;
                }
                Console::WriteLine("UniversalVkBot is unavailable for input command service. Is server working correctly?");
                continue;
            }
            $data["key"] = $result;
            //Console::WriteLine("Readline: " . $input);
        }
    }

    public static function Send(string $ip, int $port, array $data, string $uri = "cmd") : string
    {
        $url = "http://" . $ip . ":" . $port . "/" . $uri;
        $ch = curl_init($url);

        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($code != 200)
        {
            Console::WriteLine("UniversalVkBot is unavailable for input command service. Is server working correctly?");
        }
        curl_close($ch);
        return $result;
    }
}