<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\KernelInterface;

class Kernel implements KernelInterface
{
    protected array $kernels = [];

    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return $this->kernels;
    }
}