<?php

namespace RTC\Websocket;

use JetBrains\PhpStorm\Pure;
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
        $this->connections->column('conn', Table::TYPE_INT, 100);
        $this->connections->create();
    }

    public function addConnection(ConnectionInterface $connection): void
    {
        $this->connections->set((string)$connection->getIdentifier(), ['conn' => $connection->getIdentifier()]);
    }

    #[Pure] public function getConnection(int $fd): ?ConnectionInterface
    {
        foreach ($this->connections as $connectionData) {
            if ($fd == $connectionData['conn']) {
                return new Connection($this->server, $fd);
            }
        }

        return null;
    }
}