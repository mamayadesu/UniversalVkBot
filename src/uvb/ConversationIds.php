<?php

namespace uvb;

use Application\Application;

class ConversationIds
{
    private array $convids = array();
    private string $path;

    public function __construct()
    {
        $this->path = Application::GetExecutableDirectory() . "conversation_ids.json";
        if (!file_exists($this->path))
        {
            $f = fopen($this->path, "w");
            fwrite($f, json_encode(array(), JSON_PRETTY_PRINT));
            fclose($f);
        }
        else
        {
            $data2 = file_get_contents($this->path);
            $data1 = json_decode($data2, true);
            $data = array();
            foreach ($data1 as $item => $value)
            {
                if (intval($item) < 2000000000 || intval($value) < 2000000000)
                {
                    continue;
                }
                $data[$item] = $value;
            }
            $this->convids = $data;
        }
    }

    public function Get(int $id) : int
    {
        return $this->GetByBot($id);
    }

    /*
     * Алиас ConversationIds::Get(int $id) : int
     */
    public function GetByBot(int $id) : int
    {
        if (!isset($this->convids[$id]))
        {
            return 0;
        }
        return $this->convids[$id];
    }

    public function GetByAdmin(int $id) : int
    {
        $result = array_search($id, $this->convids);
        if ($result !== false)
        {
            return $result;
        }
        else
        {
            return 0;
        }
    }

    public function Set(int $botId, int $adminId) : void
    {
        $this->convids[$botId] = $adminId;
    }

    public function Save() : void
    {
        $f = fopen($this->path, "w");
        fwrite($f, json_encode($this->convids, JSON_PRETTY_PRINT));
        fclose($f);
    }
}