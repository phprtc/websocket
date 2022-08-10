<?php
declare(strict_types=1);

namespace RTC\Websocket;

use JetBrains\PhpStorm\Pure;
use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Server\Event;
use RTC\Server\Server;
use RTC\Websocket\Enums\RoomEventEnum;
use RTC\Websocket\Exceptions\RoomOverflowException;
use Swoole\Table;

class Room extends Event
{
    private Table $connections;
    private array $connMetaData = [];


    public static function create(string $name, int $size = -1): static
    {
        return new static($name, $size);
    }


    public function __construct(
        protected readonly string $name,
        protected readonly int    $size = 1000_00
    )
    {
        $this->connections = new Table($this->size);
        $this->connections->column('conn', Table::TYPE_STRING, 100);
        $this->connections->create();
    }

    /**
     * @throws RoomOverflowException
     */
    public function add(int|ConnectionInterface $connection, array $metaData = []): static
    {
        if (-1 != $this->size && $this->connections->count() == $this->size) {
            throw new RoomOverflowException("The maximum size of $this->size for room $this->name has been reached.");
        }

        $connectionId = $this->getClientId($connection);
        $this->connections->set(key: $connectionId, value: ['conn' => $connectionId]);

        // Save metadata(if any)
        if ([] != $metaData) {
            $this->connMetaData[$connectionId] = $metaData;
        }

        // Fire client add event
        $this->emit(RoomEventEnum::ON_ADD->value, [$connection]);

        return $this;
    }

    public function has(int|ConnectionInterface $connection): bool
    {
        return $this->connections->exist($this->getClientId($connection));
    }

    public function getMetaData(int|ConnectionInterface $connection): ?array
    {
        return $this->connMetaData[$this->getClientId($connection)] ?? null;
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
        $this->emit(RoomEventEnum::ON_REMOVE->value, [$connection]);
    }

    public function removeAll(): void
    {
        $connections = clone $this->connections;
        $this->connections->destroy();

        $this->emit(RoomEventEnum::ON_REMOVE_ALL->value, [$connections]);
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
        $this->emit(RoomEventEnum::ON_MESSAGE->value, [$command, $message]);

        foreach ($this->connections as $connectionData) {
            Server::get()->push(
                fd: intval($connectionData['conn']),
                data: strval(json_encode([
                    'command' => $command,
                    'data' => [
                        'sender' => "_system",
                        'message' => $message
                    ],
                    'time' => microtime(true)
                ])),
            );
        }

        return $this->connections->count();
    }

    public function sendAsClient(ConnectionInterface $connection, string $command, mixed $message): int
    {
        // Fire message event
        $this->emit(RoomEventEnum::ON_MESSAGE_ALL->value, [$command, $message, clone $this->connections]);

        foreach ($this->connections as $connectionData) {
            Server::get()->push(
                fd: intval($connectionData['conn']),
                data: strval(json_encode([
                    'command' => $command,
                    'data' => [
                        'sender' => $connection,
                        'message' => $message
                    ],
                    'time' => microtime(true)
                ])),
            );
        }

        return $this->connections->count();
    }

    #[Pure] protected function getClientId(int|ConnectionInterface $connection): string
    {
        return strval(is_int($connection) ? $connection : $connection->getIdentifier());
    }
}