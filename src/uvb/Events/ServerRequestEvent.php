<?php
declare(ticks = 1);

namespace uvb\Events;

use HttpServer\Request;
use HttpServer\Response;
use uvb\Models\Group;

class ServerRequestEvent extends Event
{
    /**
     * @ignore
     */
    private Request $request;

    /**
     * @ignore
     */
    private Response $response;

    /**
     * @ignore
     */
    private bool $preventDefaultRequestHandler = false, $isSecretKeyValid;

    /**
     * @ignore
     */
    public function __construct(?Group $group, Request $request, Response $response, bool $is_secret_key_valid)
    {
        parent::__construct($group);
        $this->request = $request;
        $this->response = $response;
        $this->isSecretKeyValid = $is_secret_key_valid;
    }

    /**
     * Возвращает объект запроса сервера
     *
     * @return Request
     */
    public function GetRequest() : Request
    {
        return $this->request;
    }

    /**
     * Возвращает объект ответа от сервера
     *
     * @return Response
     */
    public function GetResponse() : Response
    {
        return $this->response;
    }

    /**
     * Останавливает стандартный обработчик запросов
     *
     * @return void
     */
    public function PreventDefaultRequestHandler() : void
    {
        $this->preventDefaultRequestHandler = true;
    }

    /**
     * Был ли стандартный обработчик запросов остановлен
     *
     * @return bool
     */
    public function IsDefaultRequestHandlerPrevented() : bool
    {
        return $this->preventDefaultRequestHandler;
    }

    /**
     * Является ли секретный ключ бота верным. В противном случае данный метод вернёт FALSE, а метод GetGroup() в таком случае всегда будет возвращать NULL
     *
     * @return bool
     */
    public function IsSecretKeyValid() : bool
    {
        return $this->isSecretKeyValid;
    }
}