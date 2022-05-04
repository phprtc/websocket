<?php

namespace RTC\Websocket\Enums;

enum RoomEnum: int
{
    case EVENT_ON_ADD = 2000;
    case EVENT_ON_LEAVE = 2001;
    case EVENT_ON_REMOVE = 2002;
    case EVENT_ON_REMOVE_ALL = 2003;
    case EVENT_ON_MESSAGE = 2004;
    case EVENT_ON_MESSAGE_ALL = 2005;
}