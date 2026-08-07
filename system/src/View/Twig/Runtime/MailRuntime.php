<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\View\Asset\AssetResolver;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * What a template of an email may reach for.
 *
 * A message is read outside the site, so every address in it is absolute; that is the whole
 * difference from the asset helper of the web environment.
 */
final readonly class MailRuntime implements RuntimeExtensionInterface
{
    public function __construct(private AssetResolver $assets)
    {
    }

    public function asset(string $path, bool $versioned = false): string
    {
        return $this->homeUrl() . $this->assets->url($path, $versioned);
    }

    public function homeUrl(): string
    {
        return rtrim((string) config('johncms.homeurl', ''), '/');
    }

    public function copyright(): string
    {
        return (string) config('johncms.copyright', '');
    }
}
