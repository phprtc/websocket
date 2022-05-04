<?php

namespace RTC\Websocket;

use RTC\Contracts\Server\ServerInterface;
use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Contracts\Websocket\WebsocketHandlerInterface;
use Swoole\Table;

abstract class WebsocketHandler implements WebsocketHandlerInterface
{
    private Table $connections;


    public function __construct(
        protected readonly ServerInterface $server,
        protected readonly int $size = 2048
    )
    {
        $this->connections = new Table($this->size);
        $this->connections->column('conn', Table::TYPE_INT, 100);
        $this->connections->create();
    }

    public function onReady(): void
    {
    }

    public function addConnection(ConnectionInterface $connection): void
    {
        $this->connections->set((string)$connection->getIdentifier(), ['conn' => $connection->getIdentifier()]);
    }

    public function getConnection(int $fd): ?ConnectionInterface
    {
        if ($this->connections->exist((string)$fd)) {
            return new Connection($this->server, $fd);
        }

        return null;
    }
}