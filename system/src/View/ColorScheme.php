<?php

declare(strict_types=1);

namespace Johncms\View;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The color scheme chosen by the visitor, read from the siteTheme cookie. It has nothing to do
 * with the theme the site is skinned with: it only says whether the page is painted dark, light
 * or by the preference of the operating system.
 *
 * The request comes from the RequestStack rather than being held directly: this is a shared
 * service resolved from templates, and a request captured at construction would be the wrong
 * one for every request but the first under a long-running runtime.
 */
class ColorScheme
{
    private const AVAILABLE_SCHEMES = [
        'dark',
        'light',
        'auto',
    ];

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getCurrentScheme(): string
    {
        $currentScheme = $this->requestStack->getCurrentRequest()?->cookies->getString('siteTheme', 'auto') ?? 'auto';

        if (! in_array($currentScheme, self::AVAILABLE_SCHEMES, true)) {
            $currentScheme = 'auto';
        }

        return $currentScheme;
    }

    public function isDarkScheme(?string $scheme = null): bool
    {
        return ($scheme ?? $this->getCurrentScheme()) === 'dark';
    }
}
