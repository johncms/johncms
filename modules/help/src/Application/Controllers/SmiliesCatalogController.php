<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Users\User;

final readonly class SmiliesCatalogController
{
    private const USER_SMILIES_MAX = 20;

    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Smiles');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add($title, '/help/smilies/');

        $items = [];

        if ($this->currentUser->isValid()) {
            $smilies = $this->currentUser->smileys ?? [];
            $myCount = is_array($smilies) ? count($smilies) : 0;
            $items[] = [
                'url'   => '/help/smilies/my/',
                'name'  => __('My smilies'),
                'count' => $myCount . ' / ' . self::USER_SMILIES_MAX,
            ];
        }

        if ($this->currentUser->rights >= 1) {
            $items[] = [
                'url'   => '/help/smilies/admin/',
                'name'  => __('For administration'),
                'count' => count(glob(ASSETS_PATH . 'emoticons/admin/*.gif') ?: []),
            ];
        }

        $dirs = glob(ASSETS_PATH . 'emoticons/user/*', GLOB_ONLYDIR) ?: [];
        $categories = [];
        foreach ($dirs as $dir) {
            $cat = strtolower(basename($dir));
            $categories[$cat] = $this->smiliesCategories()[$cat] ?? ucfirst($cat);
        }
        asort($categories);

        foreach ($categories as $cat => $name) {
            $items[] = [
                'url'   => '/help/smilies/' . urlencode($cat) . '/',
                'name'  => $name,
                'count' => count(glob(ASSETS_PATH . 'emoticons/user/' . $cat . '/*.{gif,jpg,png}', GLOB_BRACE) ?: []),
            ];
        }

        return new ViewResponse(
            '@help/public/catalog.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'items'      => $items,
                'back_url'   => '/help/',
            ]
        );
    }

    private function smiliesCategories(): array
    {
        return [
            'animals'       => __('Animals'),
            'brawl_weapons' => __('Brawl, Weapons'),
            'emotions'      => __('Emotions'),
            'flowers'       => __('Flowers'),
            'food_alcohol'  => __('Food, Alcohol'),
            'gestures'      => __('Gestures'),
            'holidays'      => __('Holidays'),
            'love'          => __('Love'),
            'misc'          => __('Miscellaneous'),
            'music'         => __('Music, Dancing'),
            'sports'        => __('Sports'),
            'technology'    => __('Technology'),
        ];
    }
}
