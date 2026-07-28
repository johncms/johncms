<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

/**
 * The request-rate log in a flat file: fixed-size records of an unsigned-int address and a
 * timestamp, rewritten on every request with the expired entries dropped.
 *
 * The record format only holds IPv4. An address that is not IPv4 — an IPv6 visitor, or none at
 * all under the console — is logged as the loopback address, which is what this log did when it
 * lived in Environment. Real IPv6 support means changing the record format here and the `ip`
 * columns of the schema; see .claude/ipv6-support-analysis.md.
 */
final class FileRequestRateLog implements RequestRateLogInterface
{
    /** How long an entry stays in the log, in seconds. */
    private const RETENTION = 60;

    /** Size of one record: two unsigned longs, see pack()/unpack() below. */
    private const RECORD_SIZE = 8;

    private const FALLBACK_IP = '127.0.0.1';

    /**
     * The entries read by the last record() call, so the page rendering them does not read the
     * file a second time within the same request.
     *
     * @var list<string>|null
     */
    private ?array $snapshot = null;

    /**
     * @param string|null $cacheFile Defaults to the file in the cache directory. Explicit only in
     *                               tests: a scalar constructor argument without a default would
     *                               make the class unautowirable and break the container.
     */
    public function __construct(
        private readonly ?string $cacheFile = null,
    ) {
    }

    public function record(string $ip): void
    {
        $file = $this->file();
        $handle = fopen($file, file_exists($file) ? 'r+' : 'w+');

        if ($handle === false || ! flock($handle, LOCK_EX)) {
            if ($handle !== false) {
                fclose($handle);
            }

            return;
        }

        $entries = $this->readEntries($handle);
        $entries[] = ['ip' => $this->toLong($ip), 'time' => time()];

        $this->write($handle, $entries);

        $this->snapshot = array_map(
            static fn (array $entry): string => long2ip($entry['ip']),
            $entries
        );
    }

    public function recentAddresses(): array
    {
        if ($this->snapshot !== null) {
            return $this->snapshot;
        }

        $file = $this->file();

        if (! file_exists($file)) {
            return [];
        }

        $handle = fopen($file, 'r');

        if ($handle === false) {
            return [];
        }

        $entries = $this->readEntries($handle);
        fclose($handle);

        return array_map(
            static fn (array $entry): string => long2ip($entry['ip']),
            $entries
        );
    }

    /**
     * Reads the entries still inside the retention window, leaving the handle where the rewrite
     * expects it.
     *
     * @param resource $handle
     * @return list<array{ip: int, time: int}>
     */
    private function readEntries($handle): array
    {
        $entries = [];
        $now = time();

        while ($block = fread($handle, self::RECORD_SIZE)) {
            $entry = unpack('Lip/Ltime', $block);

            if ($entry === false || ($now - $entry['time']) > self::RETENTION) {
                continue;
            }

            $entries[] = ['ip' => $entry['ip'], 'time' => $entry['time']];
        }

        return $entries;
    }

    /**
     * @param resource $handle
     * @param list<array{ip: int, time: int}> $entries
     */
    private function write($handle, array $entries): void
    {
        fseek($handle, 0);
        ftruncate($handle, 0);

        foreach ($entries as $entry) {
            fwrite($handle, pack('LL', $entry['ip'], $entry['time']));
        }

        fclose($handle);
    }

    private function toLong(string $ip): int
    {
        $ipv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ?: self::FALLBACK_IP;

        return (int) sprintf('%u', ip2long($ipv4));
    }

    private function file(): string
    {
        return $this->cacheFile ?? CACHE_PATH . 'ip-requests-list.cache';
    }
}
