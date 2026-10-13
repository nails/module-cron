<?php

namespace Tests\Cron\Service;

use Nails\Cron\Service\SkipNotice;
use PHPUnit\Framework\TestCase;

class SkipNoticeTest extends TestCase
{
    private string $sPath;

    protected function setUp(): void
    {
        $this->sPath = sys_get_temp_dir() . '/cron-skip-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->sPath)) {
            unlink($this->sPath);
        }
    }

    public function test_first_skip_is_logged_and_repeats_are_silent(): void
    {
        $oNotice = new SkipNotice($this->sPath);
        $oStart  = new \DateTimeImmutable('2026-10-12 00:00:00');

        $aFirst = $oNotice->observe('Nails\\Housekeeping\\Cron\\Task\\Run', 42, $oStart);

        self::assertSame(
            ['Reached maximum allowed number of processes for Nails\\Housekeeping\\Cron\\Task\\Run; previous run still active'],
            $aFirst
        );

        for ($iMinute = 1; $iMinute <= 6; $iMinute++) {
            $aQuiet = $oNotice->observe(
                'Nails\\Housekeeping\\Cron\\Task\\Run',
                42,
                $oStart->modify('+' . $iMinute . ' minutes')
            );
            self::assertSame([], $aQuiet);
        }
    }

    public function test_release_logs_the_skip_count_and_duration(): void
    {
        $oNotice = new SkipNotice($this->sPath);
        $oStart  = new \DateTimeImmutable('2026-10-12 00:00:00');
        $sClass  = 'Nails\\Housekeeping\\Cron\\Task\\Run';

        $oNotice->observe($sClass, 42, $oStart);
        for ($iMinute = 1; $iMinute <= 6; $iMinute++) {
            $oNotice->observe($sClass, 42, $oStart->modify('+' . $iMinute . ' minutes'));
        }

        $aReleased = $oNotice->observe($sClass, null, $oStart->modify('+7 minutes'));

        self::assertSame(
            [$sClass . ' skipped 7 times over 7m, previous run still active'],
            $aReleased
        );
        self::assertSame([], $oNotice->observe($sClass, null, $oStart->modify('+8 minutes')));
    }

    public function test_a_new_process_closes_the_previous_skip(): void
    {
        $oNotice = new SkipNotice($this->sPath);
        $oStart  = new \DateTimeImmutable('2026-10-12 00:00:00');
        $sClass  = 'Nails\\Queue\\Cron\\Task\\Clean';

        $oNotice->observe($sClass, 7, $oStart);

        $aLines = $oNotice->observe($sClass, 8, $oStart->modify('+2 minutes'));

        self::assertSame(
            [
                $sClass . ' skipped 1 time over 2m, previous run still active',
                'Reached maximum allowed number of processes for ' . $sClass . '; previous run still active',
            ],
            $aLines
        );
    }
}
