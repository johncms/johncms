<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Admin\Application\DTO\IpRangeParseResult;

/**
 * Разбирает поисковый запрос по IP в диапазон (ip2long..ip2long).
 *
 * Поддерживаемые форматы:
 *  - одиночный адрес        10.5.7.1
 *  - диапазон               10.5.7.1-10.5.7.100
 *  - маска                  10.5.*.*  (символ * раскрывается в 0..255)
 */
final readonly class IpRangeParser
{
    private const IPV4_PATTERN = '#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#';

    public function parse(string $search): IpRangeParseResult
    {
        if (str_contains($search, '-')) {
            return $this->parseRange($search);
        }

        if (str_contains($search, '*')) {
            return $this->parseMask($search);
        }

        if (! preg_match(self::IPV4_PATTERN, $search)) {
            return new IpRangeParseResult(null, null, [__('Invalid IP')]);
        }

        $ip = ip2long($search);

        return new IpRangeParseResult($ip, $ip);
    }

    private function parseRange(string $search): IpRangeParseResult
    {
        [$first, $second] = array_pad(explode('-', $search, 2), 2, '');
        $errors = [];

        $first = trim($first);
        $from = null;
        if (! preg_match(self::IPV4_PATTERN, $first)) {
            $errors[] = __('First IP is entered incorrectly');
        } else {
            $from = ip2long($first);
        }

        $second = trim($second);
        $to = null;
        if (! preg_match(self::IPV4_PATTERN, $second)) {
            $errors[] = __('Second IP is entered incorrectly');
        } else {
            $to = ip2long($second);
        }

        return new IpRangeParseResult($from, $to, $errors);
    }

    private function parseMask(string $search): IpRangeParseResult
    {
        $octets = explode('.', $search);
        $low = [];
        $high = [];

        for ($i = 0; $i < 4; $i++) {
            $octet = $octets[$i] ?? '*';
            if ($octet === '*') {
                $low[$i] = '0';
                $high[$i] = '255';
            } elseif (is_numeric($octet) && $octet >= 0 && $octet <= 255) {
                $low[$i] = $octet;
                $high[$i] = $octet;
            } else {
                return new IpRangeParseResult(null, null, [__('Invalid IP')]);
            }
        }

        return new IpRangeParseResult(
            ip2long(implode('.', $low)),
            ip2long(implode('.', $high)),
        );
    }
}
