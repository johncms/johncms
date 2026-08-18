<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Users\UserImages;
use Johncms\View\Asset\AssetResolver;
use Johncms\View\Asset\ThemeEntryResolver;
use Johncms\View\Asset\Vite;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

final readonly class AssetRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private AssetResolver $assets,
        private Vite $vite,
        private ThemeEntryResolver $entries,
        private UserImages $userImages,
    ) {
    }

    /**
     * The avatar of a user, or the placeholder icon of the theme when there is none.
     */
    public function avatar(int $userId): string
    {
        $avatar = $this->userImages->avatarUrl($userId);

        return $avatar === '' ? $this->assets->url('icons/user.svg') : $avatar;
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
     * The script and style tags of a build area — "public" or "admin". The entry point behind it
     * comes from the manifest of the theme, so a layout inherited by a child theme loads the
     * bundle of that child rather than the one of the theme the layout is written in.
     *
     * The tags are markup by contract, so the template needs no raw filter to print them.
     */
    public function vite(string $area = 'public', bool $rtl = false): Markup
    {
        return new Markup($this->vite->tags($this->entries->entry($area), $rtl), 'UTF-8');
    }
}
