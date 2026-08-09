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

use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class AvatarCatalogController
{
    public function __construct(
        private NavChain $navChain,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Avatars');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add($title, '/help/avatars/');

        $dirs = glob(ASSETS_PATH . 'avatars/*', GLOB_ONLYDIR) ?: [];
        $items = [];
        foreach ($dirs as $dir) {
            $nameFile = $dir . '/name.txt';
            $items[] = [
                'url'   => '/help/avatars/' . basename($dir) . '/',
                'name'  => is_file($nameFile) ? trim((string) file_get_contents($nameFile)) : basename($dir),
                'count' => count(glob($dir . '/*.png') ?: []),
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
}
