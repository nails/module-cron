<?php

namespace Nails\Cron\Console;

class CapturedOutput
{
    /**
     * Body to copy into the cron log. Suppressed output is kept only when the command failed.
     */
    public static function body(string $sBody, bool $bLogOutput, int $iExitCode): ?string
    {
        if ($bLogOutput || $iExitCode !== 0) {
            return $sBody;
        }

        return null;
    }
}
