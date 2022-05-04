<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\FrameInterface;
use RTC\Contracts\Websocket\PayloadInterface;

class Frame implements FrameInterface
{
    protected array|string $decodedMessage;


    public function __construct(protected \Swoole\WebSocket\Frame $frame)
    {
        $decodedMessage = json_decode($this->frame->data, true);

        if (is_array($decodedMessage)) {
            $this->decodedMessage = $decodedMessage;
        } else {
            $this->decodedMessage = (string)$decodedMessage;
        }
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