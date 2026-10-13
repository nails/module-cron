<?php

namespace Nails\Cron\Console;

class LogGate
{
    public function __construct(private readonly bool $bFile)
    {
    }

    public function acceptsIdle(): bool
    {
        return !$this->bFile;
    }
}
