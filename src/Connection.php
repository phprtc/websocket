<?php

namespace RTC\Websocket;

use RTC\Contracts\Enums\WSIntendedReceiver;
use RTC\Contracts\Enums\WSSenderType;
use RTC\Contracts\Websocket\ConnectionInterface;
use RTC\Server\Server;
use Stringable;

class Connection implements Stringable, ConnectionInterface
{
    public function __construct(
        protected int $fd
    )
    {
    }

    public function __toString(): string
    {
        return strval($this->fd);
    }

    /**
     * @inheritDoc
     */
    public function send(
        string       $event,
        mixed        $data,
        WSSenderType $senderType = WSSenderType::SYSTEM,
        string       $senderId = 'system',
        int          $opcode = 1,
        int          $flags = SWOOLE_WEBSOCKET_FLAG_FIN
    ): void
    {
        if (Server::get()->exists($this->getIdentifier())) {
            Server::get()->sendWSMessage(
                fd: $this->fd,
                event: $event,
                data: $data,
                senderType: $senderType,
                senderId: $senderId,
                receiverType: WSIntendedReceiver::CLIENT,
                receiverId: strval($this->fd),
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
        Server::get()->getServer()->close($this->fd);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): int
    {
        return $this->fd;
    }
}