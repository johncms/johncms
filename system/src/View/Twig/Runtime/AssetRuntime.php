<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Http\PublicUrlResolver;
use Johncms\View\Asset\AssetResolver;
use Johncms\View\Asset\Vite;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

final readonly class AssetRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private AssetResolver $assets,
        private Vite $vite,
        private PublicUrlResolver $publicUrls,
    ) {
    }

    /**
     * The avatar of a user, or the placeholder icon of the theme when there is none.
     */
    public function avatar(int $userId): string
    {
        $avatar = UPLOAD_PATH . 'users/avatar/' . $userId . '.png';

        if ($userId > 0 && is_file($avatar)) {
            return $this->publicUrls->fromPath($avatar) . '?v=' . filemtime($avatar);
        }

        return $this->assets->url('icons/user.svg');
    }

    public function url(string $path, bool $versioned = false): string
    {
        return $this->assets->url($path, $versioned);
    }

    public function exists(string $path): bool
    {
        return $this->assets->exists($path);
    }

    /**
     * The script and style tags of a build entry point. They are markup by contract, so the
     * template needs no raw filter to print them.
     */
    public function vite(string $entry, bool $rtl = false): Markup
    {
        return new Markup($this->vite->tags($entry, $rtl), 'UTF-8');
    }
}
