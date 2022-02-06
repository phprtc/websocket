<?php

namespace RTC\Websocket;

use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Contracts\Websocket\WebsocketHandlerInterface;
use RTC\Server\Server;
use Swoole\Table;

abstract class WebsocketHandler implements WebsocketHandlerInterface
{
    protected Table $connections;

    public function __construct(
        protected readonly Server $server,
        int       $size = 2048
    )
    {
        $this->connections = new Table($size);
        $this->connections->column('path', Table::TYPE_INT, 100);
        $this->connections->create();
    }

    public function addConnection(ConnectionInterface $connection): void
    {
        $this->connections->set($connection->getIdentifier(), ['path' => $connection->getIdentifier()]);
    }

    public function getConnection(int $fd): ?ConnectionInterface
    {
        foreach ($this->connections as $connFD => $connection) {
            if ($fd == $connFD) return $connection;
        }

        return null;
    }
}