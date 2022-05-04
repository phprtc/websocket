<?php

namespace RTC\Websocket;

use RTC\Contracts\Server\ServerInterface;
use RTC\Contracts\Websocket\ConnectionInterface;

class Connection implements ConnectionInterface
{
    public function __construct(
        protected ServerInterface $server,
        protected int             $fd
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
        if ($this->server->exists($this->getIdentifier())) {
            $this->server->push(
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
        $this->server->getServer()->close($this->fd);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): int
    {
        return $this->fd;
    }
}