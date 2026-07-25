<?php

declare(strict_types=1);

namespace Johncms\System\View;

use Johncms\Http\Request;

class Theme
{
    private const AVAILABLE_THEMES = [
        'dark',
        'light',
        'auto',
    ];

    public function __construct(private Request $request)
    {
    }

    public function getCurrentTheme(): string
    {
        $currentTheme = $this->request->cookies->getString('siteTheme', 'auto');

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
