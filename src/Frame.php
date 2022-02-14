<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\FrameInterface;

class Frame implements FrameInterface
{
    protected array $decodedMessage;


    public function __construct(protected \Swoole\WebSocket\Frame $frame)
    {

    }

    /**
     * @inheritDoc
     */
    public function getFrame(): \Swoole\WebSocket\Frame
    {
        return $this->frame;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): array
    {
        if (!isset($this->decodedMessage)) {
            $this->decodedMessage = json_decode($this->frame->data, true);
        }

        return $this->decodedMessage;
    }

    /**
     * @inheritDoc
     */
    public function getRawMessage(): string
    {
        return $this->frame->data;
    }
}