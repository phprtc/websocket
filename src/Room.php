<?php
declare(strict_types=1);

namespace RTC\Websocket;

use JetBrains\PhpStorm\Pure;
use RTC\Contracts\Server\ServerInterface;
use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Server\Event;
use RTC\Websocket\Enums\RoomEnum;
use RTC\Websocket\Exceptions\RoomOverflowException;
use Swoole\Table;

class Room extends Event
{
    private Table $connections;


    public static function create(ServerInterface $server, string $name, int $size = -1): static
    {
        return new static($server, $name, $size);
    }


    public function __construct(
        protected ServerInterface $server,
        protected string          $name,
        protected int             $size = 1024
    )
    {
        $this->connections = new Table($this->size);
        $this->connections->column('conn', Table::TYPE_INT, 100);
        $this->connections->create();
    }

    /**
     * @throws RoomOverflowException
     */
    public function add(int|ConnectionInterface $connection): static
    {
        if (-1 != $this->size && $this->connections->count() == $this->size) {
            throw new RoomOverflowException("The maximum size of $this->size for room $this->name has been reached.");
        }

        $connectionId = $this->getClientId($connection);
        $this->connections->set(key: $connectionId, value: ['conn' => (int)$connectionId]);

        // Fire client add event
        $this->emit(RoomEnum::EVENT_ON_ADD->value, [$connection]);

        return $this;
    }

    public function has(int|ConnectionInterface $connection): bool
    {
        return $this->connections->exist($this->getClientId($connection));
    }

    public function count(): int
    {
        return $this->connections->count();
    }

    public function remove(int|ConnectionInterface $connection): void
    {
        $connectionId = $this->getClientId($connection);

        if ($this->connections->exist($connectionId)) {
            $this->connections->del($connectionId);
        }

        // Fire client remove event
        $this->emit(RoomEnum::EVENT_ON_REMOVE->value, [$connection]);
    }

    public function removeAll(): void
    {
        $connections = clone $this->connections;
        $this->connections->destroy();

        $this->emit(RoomEnum::EVENT_ON_REMOVE_ALL->value, [$connections]);
    }

    public function getClients(): Table
    {
        return $this->connections;
    }

    /**
     * @param string $command
     * @param mixed $message
     * @return int Number of successful recipients
     */
    public function send(string $command, mixed $message): int
    {
        // Fire message event
        $this->emit(RoomEnum::EVENT_ON_MESSAGE->value, [$command, $message]);

        foreach ($this->connections as $connectionData) {
            $this->server->push(
                fd: $connectionData['conn'],
                data: (string)json_encode([
                    'command' => $command,
                    'data' => ['message' => $message],
                    'time' => microtime(true)
                ]),
            );
        }

        return $this->connections->count();
    }

    public function sendAsClient(ConnectionInterface $connection, string $command, mixed $message): int
    {
        // Fire message event
        $this->emit(RoomEnum::EVENT_ON_MESSAGE_ALL->value, [$command, $message, clone $this->connections]);

        foreach ($this->connections as $connectionData) {
            $this->server->push(
                fd: $connectionData['conn'],
                data: (string)json_encode([
                    'command' => $command,
                    'data' => [
                        'sender' => $connection,
                        'message' => $message
                    ],
                    'time' => microtime(true)
                ]),
            );
        }

        return $this->connections->count();
    }

    #[Pure] protected function getClientId(int|ConnectionInterface $connection): string
    {
        return (string)(is_int($connection) ? $connection : $connection->getIdentifier());
    }
}