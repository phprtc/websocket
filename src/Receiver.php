<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\ReceiverInterface;

class Receiver implements ReceiverInterface
{
    public function __construct(
        protected readonly array $data
    )
    {
    }

    public function getType(): string
    {
        return $this->data['type'];
    }

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function isValid(): bool
    {
        return !array_key_exists('id', $this->data) || !array_key_exists('type', $this->data);
    }
}