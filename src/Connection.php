<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Server\Server;

class Connection implements ConnectionInterface
{
    public function __construct(
        protected int $fd
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function send(
        string $command,
        mixed  $data,
        int    $opcode = 1,
        int    $flags = SWOOLE_WEBSOCKET_FLAG_FIN
    ): void
    {
        if (Server::get()->exists($this->getIdentifier())) {
            Server::get()->push(
                fd: $this->fd,
                data: (string)json_encode([
                    'command' => $command,
                    'data' => $data,
                    'time' => microtime(true)
                ]),
                opcode: $opcode,
                flags: $flags,
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        Server::get()->getServer()->close($this->fd);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): int
    {
        return $this->fd;
    }
}