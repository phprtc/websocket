<?php

namespace RTC\Websocket;

use HttpStatusCodes\StatusCode;
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

    public function attachInfo(string $info): static
    {
        Server::get()->attachConnectionInfo(
            connection: $this->fd,
            info: $info
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function send(
        string       $event,
        mixed        $data,
        array        $meta = [],
        WSSenderType $senderType = WSSenderType::SERVER,
        string       $senderId = 'system',
        StatusCode   $status = StatusCode::OK,
        int          $opcode = 1,
        int          $flags = SWOOLE_WEBSOCKET_FLAG_FIN,
        bool         $isForwarding = false,
    ): void
    {
        $meta['is_forwarded'] = $isForwarding;

        if (Server::get()->exists($this->getIdentifier())) {
            Server::get()->sendWSMessage(
                fd: $this->fd,
                event: $event,
                data: $data,
                senderType: $senderType,
                senderId: $senderId,
                receiverType: WSIntendedReceiver::CLIENT,
                receiverId: strval($this->fd),
                meta: $meta,
                status: $status,
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