<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Modules\ModuleAssetRegistry;
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
        private ModuleAssetRegistry $moduleAssets,
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

    /**
     * One published file of one module: module_asset('blog', 'js/app.js').
     *
     * For a template of the module itself, which knows what it ships. What every page needs is
     * module_assets() below.
     */
    public function moduleAsset(string $alias, string $asset): string
    {
        return $this->moduleAssets->url($alias, $asset);
    }

    /**
     * The tags of every module switched on for this area.
     *
     * The layout prints this next to vite(): without it a module could ship a script and have no
     * way of getting it onto a page, short of the theme being edited for it.
     */
    public function moduleAssets(string $area = 'public'): Markup
    {
        $tags = '';
        foreach ($this->moduleAssets->urls($area) as $url) {
            $tags .= str_ends_with(explode('?', $url)[0], '.css')
                ? sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($url, ENT_QUOTES))
                : sprintf('<script src="%s" defer></script>', htmlspecialchars($url, ENT_QUOTES));
        }

        return new Markup($tags, 'UTF-8');
    }
}
