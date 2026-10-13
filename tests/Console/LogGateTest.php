<?php

namespace Tests\Cron\Console;

use Nails\Cron\Console\LogGate;
use PHPUnit\Framework\TestCase;

class LogGateTest extends TestCase
{
    public function test_a_file_log_drops_idle_minutes(): void
    {
        self::assertFalse((new LogGate(true))->acceptsIdle());
    }

    public function test_terminal_output_keeps_idle_minutes(): void
    {
        self::assertTrue((new LogGate(false))->acceptsIdle());
    }
}
