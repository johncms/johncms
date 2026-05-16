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
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class AvatarListController
{
    private const PER_PAGE = 50;

    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(string $id): string
    {
        $avatarDir = ASSETS_PATH . 'avatars/' . $id;

        if (! is_dir($avatarDir)) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('Wrong data'),
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => '/help/avatars/',
                'back_url_name' => __('Back'),
            ]);
        }

        $nameFile = $avatarDir . '/name.txt';
        $title = is_file($nameFile) ? htmlentities((string) file_get_contents($nameFile), ENT_QUOTES, 'utf-8') : $id;

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add(__('Avatars'), '/help/avatars/');
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        $page = max(1, (int) $this->request->getQuery('page', 1));
        $start = ($page - 1) * self::PER_PAGE;

        $files = glob($avatarDir . '/*.png') ?: [];
        $total = count($files);
        $end = min($start + self::PER_PAGE, $total);

        $items = [];
        for ($i = $start; $i < $end; $i++) {
            $baseName = pathinfo($files[$i], PATHINFO_FILENAME);
            $items[] = [
                'picture' => '/assets/avatars/' . $id . '/' . basename($files[$i]),
                'set_url' => ($this->currentUser->isValid() && is_numeric($baseName))
                    ? '/help/avatars/' . $id . '/set/' . $baseName . '/'
                    : '',
            ];
        }

        return $this->render->render('help::avatar_list', [
            'data' => [
                'items'      => $items,
                'total'      => $total,
                'per_page'   => self::PER_PAGE,
                'pagination' => $this->tools->displayPagination('/help/avatars/' . $id . '/?', $start, $total, self::PER_PAGE),
                'back_url'   => '/help/avatars/',
            ],
        ]);
    }
}
