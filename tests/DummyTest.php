<?php

namespace RTC\Websocket\Tests;

use PHPUnit\Framework\TestCase;

class DummyTest extends TestCase
{
    public function testDummy(): void
    {
        self::assertSame(1, 1);
    }
}