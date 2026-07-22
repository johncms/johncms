<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\View\Extension;

use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\ShortNumberFormatter;
use Mobicms\Render\Engine;
use Mobicms\Render\ExtensionInterface;

/**
 * Exposes the common value formatters to templates.
 */
final readonly class Formatter implements ExtensionInterface
{
    public function __construct(
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('formatNumber', [$this, 'formatNumber']);
        $engine->registerFunction('displayDate', [$this, 'displayDate']);
    }

    public function formatNumber(int|float $number): string
    {
        return ShortNumberFormatter::format($number);
    }

    public function displayDate(int $timestamp): string
    {
        return $this->dateFormatter->format($timestamp);
    }
}
