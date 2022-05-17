<?php

namespace uvb\Models;

use \Exception;

class BanRow
{
    /**
     * @ignore
     */
    private Entity $entity;

    /**
     * @ignore
     */
    private User $admin;

    /**
     * @ignore
     */
    private int $Date;

    /**
     * @ignore
     */
    private int $Reason;

    /**
     * @ignore
     */
    private string $Comment;

    /**
     * @ignore
     */
    private int $EndDate;

    public function __construct(array $sourceData)
    {
        if (!isset($sourceData["type"]) || !is_string($sourceData["type"]) || ($sourceData["type"] != "group" && $sourceData["type"] != "profile"))
        {
            throw new Exception("Invalid 'type'");
        }


    }
}