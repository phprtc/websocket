<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Server\Server;

class Connection implements ConnectionInterface
{
    public function __construct(
        protected Server $server,
        protected int    $fd
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
        $data = [
            'command' => $command,
            'data' => $data,
            'time' => time()
        ];

        $this->server->push($this->fd, json_encode($data), $opcode, $flags);
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