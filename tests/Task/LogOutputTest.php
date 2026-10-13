<?php

namespace Tests\Cron\Task;

use Nails\Cron\Task\Base;
use PHPUnit\Framework\TestCase;

class LoudTask extends Base
{
}

class QuietTask extends Base
{
    const LOG_OUTPUT = false;
}

class LogOutputTest extends TestCase
{
    public function test_tasks_log_output_unless_they_opt_out(): void
    {
        self::assertTrue((new LoudTask())->logsOutput());
        self::assertFalse((new QuietTask())->logsOutput());
    }
}
