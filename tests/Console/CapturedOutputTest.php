<?php

namespace Tests\Cron\Console;

use Nails\Cron\Console\CapturedOutput;
use PHPUnit\Framework\TestCase;

class CapturedOutputTest extends TestCase
{
    public function test_logging_tasks_keep_their_output(): void
    {
        self::assertSame('banner', CapturedOutput::body('banner', true, 0));
    }

    public function test_opt_out_discards_a_successful_body(): void
    {
        self::assertNull(CapturedOutput::body("Housekeeping: Run\nDiscovered 4 routine(s)", false, 0));
    }

    public function test_opt_out_keeps_the_body_when_the_exit_code_is_not_zero(): void
    {
        $sBody = "Housekeeping: Run\nerror while deleting";

        self::assertSame($sBody, CapturedOutput::body($sBody, false, 1));
    }
}
