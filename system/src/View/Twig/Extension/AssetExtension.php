<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Johncms\View\Twig\Runtime\AssetRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [AssetRuntime::class, 'url']),
            new TwigFunction('asset_exists', [AssetRuntime::class, 'exists']),
            new TwigFunction('vite', [AssetRuntime::class, 'vite']),
            new TwigFunction('avatar', [AssetRuntime::class, 'avatar']),
        ];
    }
}
