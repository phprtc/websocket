<?php

namespace RTC\Websocket\Enums;

enum RoomEventEnum: int
{
    case ON_ADD = 2000;
    case ON_LEAVE = 2001;
    case ON_REMOVE = 2002;
    case ON_REMOVE_ALL = 2003;
    case ON_MESSAGE = 2004;
    case ON_MESSAGE_ALL = 2005;
}