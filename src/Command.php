<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\CommandInterface;
use RTC\Contracts\Websocket\FrameInterface;
use Swoole\WebSocket\Frame;

class Command implements CommandInterface
{

    public function __construct(
        protected Frame $frame,
        protected array $decodedMessage = []
    )
    {
        if (empty($this->decodedMessage)) {
            $this->decodedMessage = json_decode($this->frame->data, true);
        }
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
    public function getCommand(): string
    {
        return $this->decodedMessage['command'];
    }

    /**
     * @inheritDoc
     */
    public function getTime(): string
    {
        return $this->decodedMessage['time'];
    }

    /**
     * @inheritDoc
     */
    public function getFrame(): FrameInterface
    {
        return new \RTC\Websocket\Frame($this->frame);
    }
}