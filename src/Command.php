<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\CommandInterface;
use RTC\Contracts\Websocket\FrameInterface;

class Command implements CommandInterface
{

    public function __construct(
        protected FrameInterface $frame
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): mixed
    {
        return $this->frame->getDecoded()['message'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getCommand(): string
    {
        return $this->frame->getDecoded()['command'];
    }

    /**
     * @inheritDoc
     */
    public function getTime(): string
    {
        return $this->frame->getDecoded()['time'];
    }

    /**
     * @inheritDoc
     */
    public function getFrame(): FrameInterface
    {
        return $this->frame;
    }
}