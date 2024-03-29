<?php
declare(strict_types=1);

namespace RTC\Websocket;

use HttpStatusCodes\StatusCode;
use JetBrains\PhpStorm\Pure;
use RTC\Contracts\Enums\WSEvent;
use RTC\Contracts\Enums\WSIntendedReceiver;
use RTC\Contracts\Enums\WSSenderType;
use RTC\Contracts\Server\ServerInterface;
use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Contracts\Websocket\RoomInterface;
use RTC\Server\Event;
use RTC\Server\Server;
use RTC\Websocket\Enums\RoomEventEnum;
use RTC\Websocket\Exceptions\RoomOverflowException;
use Swoole\Table;

class Room extends Event implements RoomInterface
{
    private Table $connections;


    public static function create(ServerInterface $server, string $name, int $size = -1): static
    {
        return new static($server, $name, $size);
    }


    public function __construct(
        public readonly ServerInterface $server,
        protected readonly string       $name,
        protected readonly int          $size = 1000
    )
    {
        $this->connections = new Table($this->size);
        $this->connections->column('conn', Table::TYPE_STRING, 100);
        $this->connections->create();

        $this->server->attachRoom($this);
    }

    /**
     * @throws RoomOverflowException
     */
    public function add(
        int|ConnectionInterface $connection,
        bool                    $notifyUsers = true,
        ?string                 $joinedMessage = null,
    ): static
    {
        if (-1 != $this->size && $this->connections->count() == $this->size) {
            throw new RoomOverflowException("The maximum size of $this->size for room $this->name has been reached.");
        }

        $connectionId = $this->getConnectionId($connection);
        $this->connections->set(key: $connectionId, value: ['conn' => $connectionId]);

        // Fire client add event
        $this->emit(RoomEventEnum::ON_ADD->value, [$connection]);

        // Notify room clients
        if ($notifyUsers) {
            $this->sendMessage(
                senderType: WSSenderType::SERVER,
                senderId: WSSenderType::SERVER->value,
                fd: intval($this->getConnectionId($connection)),
                event: WSEvent::ROOM_JOINED->value,
                message: 'room joined successfully',
                meta: [
                    'room' => $this->name,
                    'user_sid' => $connectionId,
                    'user_info' => $this->server->getConnectionInfo($connection),
                ],
            );

            $this->send(
                event: WSEvent::ROOM_USER_JOINED->value,
                message: $joinedMessage ?? sprintf('<i>#%s</i> joined this room', $info['user_name'] ?? $connectionId),
                meta: [
                    'user_sid' => $connectionId,
                    'user_info' => $this->server->getConnectionInfo($connection),
                ],
                excludeIds: [$connectionId]
            );
        }

        return $this;
    }

    public function has(int|ConnectionInterface $connection): bool
    {
        return $this->connections->exist($this->getConnectionId($connection));
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

            // Fire client remove event
            $this->emit(RoomEventEnum::ON_REMOVE->value, [$connection]);

            // Notify room clients
            if ($notifyUsers) {
                $this->sendMessage(
                    senderType: WSSenderType::SERVER,
                    senderId: WSSenderType::SERVER->value,
                    fd: intval($this->getConnectionId($connection)),
                    event: WSEvent::ROOM_LEFT->value,
                    message: 'room left successfully',
                    meta: [
                        'user_sid' => $connectionId,
                        'user_info' => $this->server->getConnectionInfo($connection),
                    ],
                );

                $this->send(
                    event: WSEvent::ROOM_USER_LEFT->value,
                    message: $leaveMessage ?? sprintf('<i>#%s</i> left this room', $connectionId),
                    meta: [
                        'user_sid' => $connectionId,
                        'user_info' => $this->server->getConnectionInfo($connection),
                    ],
                    excludeIds: [$connectionId]
                );
            }
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
     * @param StatusCode $status
     * @param bool $isForwarding
     * @return int Number of successful recipients
     */
    public function send(
        string     $event,
        mixed      $message,
        array      $meta = [],
        array      $excludeIds = [],
        StatusCode $status = StatusCode::OK,
        bool       $isForwarding = false,
    ): int
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
                senderType: WSSenderType::SERVER,
                senderId: WSSenderType::SERVER->value,
                fd: intval($connectionData['conn']),
                event: $event,
                message: $message,
                meta: $meta,
                status: $status,
                isForwarding: $isForwarding
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
     * @param StatusCode $status
     * @param bool $isForwarding
     * @return int
     */
    public function sendAsClient(
        ConnectionInterface $connection,
        string              $event,
        mixed               $message,
        array               $meta = [],
        StatusCode          $status = StatusCode::OK,
        bool                $isForwarding = false,
    ): int
    {
        // Fire message event
        $this->emit(RoomEventEnum::ON_MESSAGE_ALL->value, [$event, $message, $this->connections]);

        foreach ($this->connections as $connectionData) {
            $this->sendMessage(
                senderType: WSSenderType::USER,
                senderId: strval($connection->getIdentifier()),
                fd: intval($connectionData['conn']),
                event: $event,
                message: $message,
                meta: $meta,
                status: $status,
                isForwarding: $isForwarding
            );
        }

        return $this->connections->count();
    }

    protected function sendMessage(
        WSSenderType $senderType,
        string       $senderId,
        int          $fd,
        string       $event,
        mixed        $message,
        array        $meta = [],
        StatusCode   $status = StatusCode::OK,
        bool         $isForwarding = false,
    ): void
    {
        $meta['is_forwarded'] = $isForwarding;

        $this->server->sendWSMessage(
            fd: $fd,
            event: $event,
            data: $isForwarding
                ? $message
                : ['message' => $message],
            senderType: $senderType,
            senderId: $senderId,
            receiverType: WSIntendedReceiver::ROOM,
            receiverId: $this->name,
            meta: $meta,
            status: $status,
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

    public function listConnections(bool $withInfo = true): array
    {
        $connections = [];

        if (!$withInfo) {
            foreach ($this->connections as $connectionData) {
                $connections[] = $connectionData['conn'];
            }

            return $connections;
        }

        foreach ($this->connections as $connectionData) {
            $connections[$connectionData['conn']] = Server::get()->getConnectionInfo(intval($connectionData['conn']));
        }

        return $connections;
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