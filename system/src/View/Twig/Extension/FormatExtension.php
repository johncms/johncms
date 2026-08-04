<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Johncms\View\Twig\Runtime\FormatRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_number', [FormatRuntime::class, 'formatNumber']),
            new TwigFilter('display_date', [FormatRuntime::class, 'displayDate']),
        ];
    }
}
