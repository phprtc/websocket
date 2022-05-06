<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\FrameInterface;

class Frame implements FrameInterface
{
    protected array $decodedMessage = [];
    protected float $serverTime;


    public function __construct(protected \Swoole\WebSocket\Frame $frame, ?array $decodedMessage = null)
    {
        $this->serverTime = microtime(true);
        $decodedMessage ??= json_decode($this->frame->data, true);

        if (is_array($decodedMessage)) {
            $this->decodedMessage = $decodedMessage;
        }
    }

    public function getRaw(): string
    {
        return $this->frame->data;
    }

    public function getDecoded(): array
    {
        return $this->decodedMessage;
    }

    public function getFd(): int
    {
        return $this->frame->fd;
    }

    public function getOpCode(): int
    {
        return $this->frame->opcode;
    }

    public function getSwooleFrame(): \Swoole\WebSocket\Frame
    {
        return $this->frame;
    }

    public function getServerTime(): float
    {
        return $this->serverTime;
    }
}