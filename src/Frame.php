<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\FrameInterface;
use RTC\Contracts\Websocket\PayloadInterface;

class Frame implements FrameInterface
{
    protected array $decodedMessage;


    public function __construct(protected \Swoole\WebSocket\Frame $frame)
    {
        $this->decodedMessage = json_decode($this->frame->data, true);
    }

    /**
     * @inheritDoc
     */
    public function getCommand(): string|null
    {
        return $this->decodedMessage['command'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): mixed
    {
        return $this->decodedMessage['message'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getTime(): string|null
    {
        return $this->decodedMessage['time'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): PayloadInterface
    {
        return new Payload($this->frame);
    }
}