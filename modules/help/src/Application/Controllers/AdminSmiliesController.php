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

final readonly class AdminSmiliesController
{
    private const USER_SMILEYS_MAX = 20;

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
        if ($this->currentUser->rights < 1) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'   => __('For administration'),
                'type'    => 'alert-danger',
                'message' => __('Access forbidden'),
            ]);
        }

        $title = __('For administration');

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

        $userSmileys = is_array($this->currentUser->smileys) ? $this->currentUser->smileys : [];

        $files = [];
        $dir = opendir(ASSETS_PATH . 'emoticons/admin');
        while (($file = readdir($dir)) !== false) {
            if ($file !== '.' && $file !== '..' && $file !== 'name.dat' && $file !== '.svn' && $file !== 'index.php') {
                $files[] = $file;
            }
        }
        closedir($dir);

        $total = count($files);
        $end = min($start + $kmess, $total);

        $items = [];
        for ($i = $start; $i < $end; $i++) {
            $smile = preg_replace('#^(.*?)\.(gif|jpg|png)$#isU', '$1', $files[$i], 1);
            $items[] = [
                'can_add'   => $this->currentUser->isValid() && ! in_array($smile, $userSmileys),
                'lat_smile' => $smile,
                'smile'     => $this->tools->trans($smile),
                'picture'   => '/assets/emoticons/admin/' . $files[$i],
            ];
        }

        return $this->render->render('help::smiles_list', [
            'data' => [
                'items'              => $items,
                'total'              => $total,
                'user_smiles_current' => count($userSmileys),
                'user_smiles_max'    => self::USER_SMILEYS_MAX,
                'pagination'         => $this->tools->displayPagination('/help/smilies/admin/?', $start, $total, $kmess),
                'form_action'        => '/help/smilies/set/?adm=1&page=' . $page,
                'back_url'           => '/help/smilies/',
            ],
        ]);
    }
}
