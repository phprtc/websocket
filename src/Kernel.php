<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\KernelInterface;

class Kernel implements KernelInterface
{
    protected array $handlers = [];

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public function hasHandlers(): bool
    {
        return !empty($this->handlers);
    }
}