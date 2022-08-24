<?php
declare(ticks = 1);

namespace uvb;

final class cmm
{
    public static ConsoleMessagesManager $consoleMessagesManager;
    public static Bot $bot;

    public static function g(string $msgId, array $params = []) : string
    {
        return cmm::$consoleMessagesManager->GetMessage($msgId, $params);
    }

    public static function l(string $msgId, array $params = []) : void
    {
        self::$bot->GetLogger()->Log(cmm::$consoleMessagesManager->GetMessage($msgId, $params));
    }

    public static function w(string $msgId, array $params = []) : void
    {
        self::$bot->GetLogger()->Warn(cmm::$consoleMessagesManager->GetMessage($msgId, $params));
    }

    public static function e(string $msgId, array $params = []) : void
    {
        self::$bot->GetLogger()->Error(cmm::$consoleMessagesManager->GetMessage($msgId, $params));
    }

    public static function c(string $msgId, array $params = []) : void
    {
        self::$bot->GetLogger()->Critical(cmm::$consoleMessagesManager->GetMessage($msgId, $params));
    }
}