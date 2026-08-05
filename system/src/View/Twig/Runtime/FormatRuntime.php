<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\ShortNumberFormatter;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class FormatRuntime implements RuntimeExtensionInterface
{
    public function __construct(private DateFormatterInterface $dateFormatter)
    {
    }

    public function formatNumber(int|float $number): string
    {
        return ShortNumberFormatter::format($number);
    }

    public function displayDate(int $timestamp): string
    {
        return $this->dateFormatter->format($timestamp);
    }

    public function formatSize(int $bytes): string
    {
        return format_size($bytes);
    }
}
