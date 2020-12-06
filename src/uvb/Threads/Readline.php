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
        $result = $this->Send($data);
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
            $result = $this->Send($data);
            if ($result == "fail" || $result == "Wrong URI")
            {
                exit;
            }
            $data["key"] = $result;
            //Console::WriteLine("Readline: " . $input);
        }
    }

    public function Send(array $data) : string
    {
        $url = "http://" . $this->ip . ":" . $this->port . "/cmd";
        $ch = curl_init($url);

        $payload = json_encode($data);
        //var_dump($payload);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_RESPONSE_CODE, true);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($code != 200)
        {
            exit;
        }
        curl_close($ch);
        return $result;
    }
}