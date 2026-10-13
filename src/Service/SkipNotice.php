<?php

namespace Nails\Cron\Service;

class SkipNotice
{
    public function __construct(private readonly string $sPath)
    {
    }

    /**
     * Record that $sClass is blocked by process $iProcessId, or that the slot
     * is free when $iProcessId is null.
     *
     * @return string[] Lines to write. Empty when this minute should stay quiet.
     */
    public function observe(string $sClass, ?int $iProcessId, \DateTimeInterface $oNow): array
    {
        if ($iProcessId === null && !is_file($this->sPath)) {
            return [];
        }

        $hFile = $this->open();

        try {
            if (!flock($hFile, LOCK_EX)) {
                return [];
            }

            $aState   = $this->decode((string) stream_get_contents($hFile));
            $aHold    = $aState[$sClass] ?? null;
            $aLines   = [];
            $bChanged = false;

            if ($iProcessId === null) {
                if (is_array($aHold)) {
                    unset($aState[$sClass]);
                    $aLines[] = $this->summary($sClass, $aHold, $oNow);
                    $bChanged = true;
                }
            } else {
                if (is_array($aHold) && (int) ($aHold['process_id'] ?? 0) !== $iProcessId) {
                    $aLines[] = $this->summary($sClass, $aHold, $oNow);
                    $aHold    = null;
                    $bChanged = true;
                }

                if (!is_array($aHold)) {
                    $aState[$sClass] = [
                        'process_id' => $iProcessId,
                        'since'      => $oNow->format('c'),
                        'skips'      => 1,
                    ];
                    $aLines[] = sprintf(
                        'Reached maximum allowed number of processes for %s; previous run still active',
                        $sClass
                    );
                    $bChanged = true;
                } else {
                    $aHold['skips']  = (int) $aHold['skips'] + 1;
                    $aState[$sClass] = $aHold;
                    $bChanged        = true;
                }
            }

            if ($bChanged) {
                $this->store($hFile, $aState);
            }
            flock($hFile, LOCK_UN);

            return $aLines;
        } finally {
            fclose($hFile);
        }
    }

    /**
     * @param array{process_id?: int, since?: string, skips?: int} $aHold
     */
    private function summary(string $sClass, array $aHold, \DateTimeInterface $oNow): string
    {
        $oSince   = new \DateTimeImmutable((string) ($aHold['since'] ?? $oNow->format('c')));
        $iSeconds = max(0, $oNow->getTimestamp() - $oSince->getTimestamp());

        $iSkips = (int) ($aHold['skips'] ?? 0);

        return sprintf(
            '%s skipped %d %s over %s, previous run still active',
            $sClass,
            $iSkips,
            $iSkips === 1 ? 'time' : 'times',
            $this->formatDuration($iSeconds)
        );
    }

    private function formatDuration(int $iSeconds): string
    {
        if ($iSeconds >= 3600) {
            $iHours   = intdiv($iSeconds, 3600);
            $iMinutes = intdiv($iSeconds % 3600, 60);

            return $iMinutes > 0 ? sprintf('%dh %dm', $iHours, $iMinutes) : sprintf('%dh', $iHours);
        }

        if ($iSeconds >= 60) {
            return sprintf('%dm', intdiv($iSeconds, 60));
        }

        return sprintf('%ds', $iSeconds);
    }

    /**
     * @return resource
     */
    private function open()
    {
        $sDir = dirname($this->sPath);
        if (!is_dir($sDir)) {
            mkdir($sDir, 0750, true);
        }

        $hFile = fopen($this->sPath, 'c+');
        if ($hFile === false) {
            throw new \RuntimeException('Unable to open cron skip state at ' . $this->sPath);
        }

        return $hFile;
    }

    /**
     * @return array<string, array{process_id: int, since: string, skips: int}>
     */
    private function decode(string $sJson): array
    {
        if ($sJson === '') {
            return [];
        }

        $aDecoded = json_decode($sJson, true);

        return is_array($aDecoded) ? $aDecoded : [];
    }

    /**
     * @param resource $hFile
     * @param array<string, array{process_id: int, since: string, skips: int}> $aState
     */
    private function store($hFile, array $aState): void
    {
        $sJson = json_encode($aState, JSON_UNESCAPED_SLASHES);
        ftruncate($hFile, 0);
        rewind($hFile);
        fwrite($hFile, $sJson === false ? '{}' : $sJson);
        fflush($hFile);
    }
}
