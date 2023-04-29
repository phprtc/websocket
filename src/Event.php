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
    public function getMessage(): mixed
    {
        return $this->frame->getDecoded()['message'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getRoom(): ?string
    {
        return $this->frame->getDecoded()['room'] ?? null;
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
        return $value === $this->getEvent();
    }

    /**
     * @inheritDoc
     */
    public function getEvent(): string
    {
        return $this->frame->getDecoded()['event'];
    }

    public function getReceiver(): ReceiverInterface
    {
        return $this->receiver;
    }
}