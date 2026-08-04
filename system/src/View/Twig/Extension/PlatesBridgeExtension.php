<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Johncms\View\Twig\Runtime\PlatesRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Lets a Twig template pull in a partial that is still written for Plates — a pagination block,
 * a breadcrumb trail, an alert. It is what allows a page to move to Twig before the partials it
 * uses do.
 */
final class PlatesBridgeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('plates', [PlatesRuntime::class, 'render']),
        ];
    }
}
