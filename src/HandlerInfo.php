<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\HandlerInfoInterface;

class HandlerInfo implements HandlerInfoInterface
{
    public function __construct(protected string $path)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }
}