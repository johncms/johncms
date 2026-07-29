<?php

declare(strict_types=1);

namespace Johncms\System\View;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The theme chosen by the visitor, read from the siteTheme cookie.
 *
 * The request comes from the RequestStack rather than being held directly: this is a shared
 * service resolved from templates, and a request captured at construction would be the wrong
 * one for every request but the first under a long-running runtime.
 */
class Theme
{
    private const AVAILABLE_THEMES = [
        'dark',
        'light',
        'auto',
    ];

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getCurrentTheme(): string
    {
        $currentTheme = $this->requestStack->getCurrentRequest()?->cookies->getString('siteTheme', 'auto') ?? 'auto';

        if (! in_array($currentTheme, self::AVAILABLE_THEMES, true)) {
            $currentTheme = 'auto';
        }

        return $currentTheme;
    }

    public function isDarkTheme(?string $theme = null): bool
    {
        return ($theme ?? $this->getCurrentTheme()) === 'dark';
    }
}
