<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\EventInterface;
use RTC\Contracts\Websocket\FrameInterface;
use RTC\Contracts\Websocket\ReceiverInterface;

class Event implements EventInterface
{
    protected readonly ReceiverInterface $receiver;

    public function __construct(
        protected FrameInterface $frame
    )
    {
        $this->receiver = new Receiver($this->frame->getDecoded()['receiver']);
    }

    /**
     * @inheritDoc
     */
    public function getData(): mixed
    {
        return $this->frame->getDecoded()['data'] ?? null;
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

    /**
     * @inheritDoc
     */
    public function eventIs(string $value): bool
    {
        return $value === $this->getName();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->frame->getDecoded()['event'];
    }

    /**
     * @inheritDoc
     */
    public function getReceiver(): ReceiverInterface
    {
        return $this->receiver;
    }
}