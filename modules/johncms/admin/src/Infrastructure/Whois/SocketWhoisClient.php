<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Whois;

use Johncms\Modules\Admin\Domain\Services\WhoisClientInterface;

final class SocketWhoisClient implements WhoisClientInterface
{
    private const PORT = 43;
    private const TIMEOUT = 5;

    /** @var list<string> */
    private const SERVERS = [
        // 'whois.afrinic.net',  // Africa — отдаёт timeout
        'whois.lacnic.net',      // Латинская Америка и Карибы (возвращает данные по всем регионам)
        'whois.apnic.net',       // Азиатско-Тихоокеанский регион
        'whois.arin.net',        // Северная Америка
        'whois.ripe.net',        // Европа, Ближний Восток и Центральная Азия
    ];

    public function lookup(string $ip): array
    {
        $results = [];

        foreach (self::SERVERS as $server) {
            $result = $this->query($server, $ip);
            if ($result !== '' && ! in_array($result, $results, true)) {
                $results[$server] = $result;
            }
        }

        return $results;
    }

    private function query(string $whoisServer, string $domain): string
    {
        $fp = @fsockopen($whoisServer, self::PORT, $errno, $errstr, self::TIMEOUT);
        if (! $fp) {
            return '';
        }

        fwrite($fp, $domain . "\r\n");
        $out = '';
        while (! feof($fp)) {
            $out .= fgets($fp);
        }
        fclose($fp);

        $lower = strtolower($out);
        if (str_contains($lower, 'error') || str_contains($lower, 'not allocated')) {
            return '';
        }

        $res = '';
        foreach (explode("\n", $out) as $row) {
            $row = trim($row);
            if ($row !== '' && $row[0] !== '#' && $row[0] !== '%') {
                $res .= $row . "\n";
            }
        }

        return trim($res);
    }
}
