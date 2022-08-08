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
        protected readonly int             $size = 1000_000
    )
    {
        $this->connections = new Table($this->size);
        $this->connections->column('conn', Table::TYPE_STRING, 100);
        $this->connections->create();
    }

    public function onReady(): void
    {
    }

    public function addConnection(ConnectionInterface $connection): void
    {
        $connId = strval($connection->getIdentifier());
        $this->connections->set($connId, ['conn' => $connId]);
    }

    public function getConnection(int $fd): ?ConnectionInterface
    {
        if ($this->connections->exist(strval($fd))) {
            return new Connection($fd);
        }

        return null;
    }
}