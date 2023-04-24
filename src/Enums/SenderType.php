<?php

namespace RTC\Websocket\Enums;

enum SenderType
{
    case USER;
    case SYSTEM;

    public function getValue(): string
    {
        return match ($this) {
            self::USER => 'user',
            self::SYSTEM => 'system'
        };
    }
}
