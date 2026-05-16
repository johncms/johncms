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
use Johncms\Http\PageMeta;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class MySmiliesController
{
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

    public function __invoke(): string
    {
        if (! $this->currentUser->isValid()) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'         => __('Access denied'),
                'type'          => 'alert-danger',
                'message'       => __('You are not logged in'),
                'back_url'      => '/help/smilies/',
                'back_url_name' => __('Back'),
            ]);
        }

        $title = __('My smilies');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add(__('Smiles'), '/help/smilies/');
        $this->navChain->add($title);

        $page = max(1, (int) $this->request->getQuery('page', 1));
        $meta = new PageMeta($title . ' — ' . __('Smiles'), $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $title,
            'description' => $meta->description,
        ]);
        $kmess = $this->currentUser->config->kmess;
        $start = ($page - 1) * $kmess;

        $allSmileys = is_array($this->currentUser->smileys) ? $this->currentUser->smileys : [];
        $total = count($allSmileys);

        $smileysPage = $allSmileys;
        if ($total > $kmess) {
            $chunks = array_chunk($allSmileys, $kmess, true);
            $chunkIndex = (int) floor($start / $kmess);
            $smileysPage = $chunks[$chunkIndex] ?? $chunks[0];
        }

        $items = [];
        foreach ($smileysPage as $value) {
            $smile = ':' . $value . ':';
            $items[] = [
                'can_del'   => true,
                'lat_smile' => $value,
                'smile'     => $this->tools->trans($smile),
                'picture'   => $this->tools->smilies($smile, $this->currentUser->rights >= 1 ? 1 : 0),
            ];
        }

        return $this->render->render('help::my_smiles_list', [
            'data' => [
                'items'       => $items,
                'total'       => $total,
                'pagination'  => $this->tools->displayPagination('/help/smilies/my/?', $start, $total, $kmess),
                'form_action' => '/help/smilies/set/?page=' . $page,
                'back_url'    => '/help/smilies/',
            ],
        ]);
    }
}
