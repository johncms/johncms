<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\View;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The cookie banner is a feature of this module, so the function that describes it comes from
 * here rather than from the core: the theme layout only lays it out.
 */
final class CookieBannerExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cookie_banner', [CookieBannerRuntime::class, 'banner']),
        ];
    }
}
