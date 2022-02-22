<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\PayloadInterface;
use Swoole\WebSocket\Frame;

class Payload implements PayloadInterface
{
    protected array $decodedMessage;
    protected float $serverTime;

    public function __construct(protected Frame $frame)
    {
        $this->decodedMessage = json_decode($this->frame->data, true);
        $this->serverTime = microtime(true);
    }

    /**
     * @inheritDoc
     */
    public function getRaw(): string
    {
        return $this->frame->data;
    }

    /**
     * @inheritDoc
     */
    public function getDecoded(): array
    {
        return $this->decodedMessage;
    }

    /**
     * @inheritDoc
     */
    public function getSwooleFrame(): Frame
    {
        return $this->frame;
    }

    /**
     * @inheritDoc
     */
    public function getServerTime(): string
    {
        return (string)$this->serverTime;
    }
}