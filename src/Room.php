<?php
declare(strict_types=1);

namespace RTC\Websocket;

use JetBrains\PhpStorm\Pure;
use RTC\Contracts\Server\ServerInterface;
use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Contracts\Websocket\RoomInterface;
use RTC\Server\Event;
use RTC\Server\Server;
use RTC\Websocket\Enums\RoomEventEnum;
use RTC\Websocket\Enums\SenderType;
use RTC\Websocket\Exceptions\RoomOverflowException;
use Swoole\Table;

class Room extends Event implements RoomInterface
{
    private Table $connections;
    private array $connMetaData = [];


    public static function create(ServerInterface $server, string $name, int $size = -1): static
    {
        return new static($server, $name, $size);
    }


    public function __construct(
        public readonly ServerInterface $server,
        protected readonly string          $name,
        protected readonly int             $size = 1000
    )
    {
        $this->connections = new Table($this->size);
        $this->connections->column('conn', Table::TYPE_STRING, 100);
        $this->connections->create();

//        $this->server->
    }

    /**
     * @throws RoomOverflowException
     */
    public function add(
        int|ConnectionInterface $connection,
        array                   $metaData = [],
        bool                    $notifyUsers = true,
        ?string                 $joinedMessage = null,
    ): static
    {
        if (-1 != $this->size && $this->connections->count() == $this->size) {
            throw new RoomOverflowException("The maximum size of $this->size for room $this->name has been reached.");
        }

        $connectionId = $this->getConnectionId($connection);
        $this->connections->set(key: $connectionId, value: ['conn' => $connectionId]);

        // Save metadata(if any)
        if ([] != $metaData) {
            $this->connMetaData[$connectionId] = $metaData;
        }

        // Fire client add event
        $this->emit(RoomEventEnum::ON_ADD->value, [$connection]);

        // Notify room clients
        if ($notifyUsers) {
            $this->sendMessage(
                senderType: SenderType::SYSTEM,
                senderFd: null,
                fd: intval($this->getConnectionId($connection)),
                event: 'room.joined',
                message: 'room joined successfully',
                meta: [
                    'room' => $this->name,
                    'user_sid' => $connectionId,
                ],
            );

            $this->send(
                event: 'room.join',
                message: $joinedMessage ?? sprintf('<i>%s</i> joined this room', $metaData['user_name'] ?? $connectionId),
                meta: ['user_sid' => $connectionId],
                excludeIds: [$connectionId]
            );
        }

        return $this;
    }

    public function has(int|ConnectionInterface $connection): bool
    {
        return $this->connections->exist($this->getConnectionId($connection));
    }

    public function getMetaData(int|ConnectionInterface $connection): ?array
    {
        return $this->connMetaData[$this->getConnectionId($connection)] ?? null;
    }

    public function count(): int
    {
        return $this->connections->count();
    }

    public function remove(int|ConnectionInterface $connection, bool $notifyUsers = true, ?string $leaveMessage = null): void
    {
        $connectionId = $this->getConnectionId($connection);

        if ($this->connections->exist($connectionId)) {
            $this->connections->del($connectionId);
        }

        // Fire client remove event
        $this->emit(RoomEventEnum::ON_REMOVE->value, [$connection]);

        // Notify room clients
        if ($notifyUsers) {
            $this->sendMessage(
                senderType: SenderType::SYSTEM,
                senderFd: null,
                fd: intval($this->getConnectionId($connection)),
                event: 'room.left',
                message: 'room left successfully',
                meta: ['user_sid' => $connectionId],
            );

            $this->send(
                event: 'room.leave',
                message: $leaveMessage ?? sprintf('<i>%s</i> left this room', $connectionId),
                meta: ['user_sid' => $connectionId],
                excludeIds: [$connectionId]
            );
        }
    }

    public function removeAll(): void
    {
        $connections = $this->connections;

        foreach ($connections as $connection) {
            $this->remove($connection);
        }

        $this->emit(RoomEventEnum::ON_REMOVE_ALL->value, [$connections]);
    }

    public function getClients(): Table
    {
        return $this->connections;
    }

    /**
     * @param string $event
     * @param mixed $message
     * @param array $meta
     * @param array $excludeIds connection ids that will not receive this message
     * @return int Number of successful recipients
     */
    public function send(string $event, mixed $message, array $meta = [], array $excludeIds = []): int
    {
        // Fire message event
        $this->emit(RoomEventEnum::ON_MESSAGE->value, [$event, $message]);

        $hasExcludableClients = [] != $excludeIds;

        foreach ($this->connections as $connectionData) {
            $connId = $connectionData['conn'];

            // Skip excludable ids
            if ($hasExcludableClients && in_array($connId, $excludeIds)) {
                continue;
            }

            $this->sendMessage(
                senderType: SenderType::SYSTEM,
                senderFd: null,
                fd: intval($connectionData['conn']),
                event: $event,
                message: $message,
                meta: $meta
            );
        }

        return $this->connections->count();
    }

    /**
     * Send message as a client
     *
     * @param ConnectionInterface $connection
     * @param string $event
     * @param mixed $message
     * @param array $meta
     * @return int
     */
    public function sendAsClient(ConnectionInterface $connection, string $event, mixed $message, array $meta = []): int
    {
        // Fire message event
        $this->emit(RoomEventEnum::ON_MESSAGE_ALL->value, [$event, $message, $this->connections]);

        foreach ($this->connections as $connectionData) {
            $this->sendMessage(
                senderType: SenderType::USER,
                senderFd: $connection->getIdentifier(),
                fd: intval($connectionData['conn']),
                event: $event,
                message: $message,
                meta: $meta
            );
        }

        return $this->connections->count();
    }

    protected function sendMessage(SenderType $senderType, ?int $senderFd, int $fd, string $event, string $message, array $meta = []): void
    {
        $this->server->push(
            fd: $fd,
            data: strval(json_encode([
                'event' => $event,
                'meta' => $meta,
                'time' => microtime(true),
                'data' => [
                    'sender_type' => $senderType->getValue(),
                    'sender_sid' => $senderFd,
                    'message' => $message,
                ],
            ])),
        );
    }

    #[Pure] protected function getConnectionId(int|ConnectionInterface $connection): string
    {
        return strval(is_int($connection) ? $connection : $connection->getIdentifier());
    }

    protected function getConnection(int|ConnectionInterface $connection): ConnectionInterface
    {
        if (is_int($connection)) {
            return Server::get()->makeConnection($connection);
        }

        return $connection;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}