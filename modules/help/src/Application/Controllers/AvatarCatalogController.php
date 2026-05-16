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
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class AvatarCatalogController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(): string
    {
        $title = __('Avatars');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add($title, '/help/avatars/');

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        $dirs = glob(ASSETS_PATH . 'avatars/*', GLOB_ONLYDIR) ?: [];
        $items = [];
        foreach ($dirs as $dir) {
            $nameFile = $dir . '/name.txt';
            $items[] = [
                'url'   => '/help/avatars/' . basename($dir) . '/',
                'name'  => is_file($nameFile) ? htmlentities((string) file_get_contents($nameFile), ENT_QUOTES, 'utf-8') : basename($dir),
                'count' => count(glob($dir . '/*.png') ?: []),
            ];
        }

        return $this->render->render('help::avatars', [
            'data' => [
                'items'    => $items,
                'back_url' => '/help/',
            ],
        ]);
    }
}
