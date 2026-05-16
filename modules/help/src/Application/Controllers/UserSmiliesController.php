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

final readonly class UserSmiliesController
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

    public function __invoke(string $cat): string
    {
        $validCats = array_map('basename', glob(ASSETS_PATH . 'emoticons/user/*', GLOB_ONLYDIR) ?: []);

        if (! in_array($cat, $validCats, true)) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('Wrong data'),
                'type'          => 'alert-danger',
                'message'       => __('The directory does not exist'),
                'back_url'      => '/help/smilies/',
                'back_url_name' => __('Back'),
            ]);
        }

        $title = $this->smiliesCategories()[$cat] ?? ucfirst(htmlspecialchars($cat));

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

        $smileys = glob(ASSETS_PATH . 'emoticons/user/' . $cat . '/*.{gif,jpg,png}', GLOB_BRACE) ?: [];
        $total = count($smileys);
        $end = min($start + $kmess, $total);

        $data = [
            'items'    => [],
            'total'    => $total,
            'back_url' => '/help/smilies/',
        ];

        if ($total > 0) {
            $userSmileys = [];
            if ($this->currentUser->isValid()) {
                $userSmileys = is_array($this->currentUser->smileys) ? $this->currentUser->smileys : [];
                $data['user_smiles_current'] = count($userSmileys);
                $data['user_smiles_max'] = self::USER_SMILEYS_MAX;
            }

            $items = [];
            for ($i = $start; $i < $end; $i++) {
                $smile = preg_replace('#^(.*?)\.(gif|jpg|png)$#isU', '$1', basename($smileys[$i]));
                $items[] = [
                    'can_add'   => $this->currentUser->isValid() && ! in_array($smile, $userSmileys),
                    'lat_smile' => $smile,
                    'smile'     => $this->tools->trans($smile),
                    'picture'   => '/assets/emoticons/user/' . $cat . '/' . basename($smileys[$i]),
                ];
            }

            $data['items'] = $items;
            $data['pagination'] = $this->tools->displayPagination('/help/smilies/' . urlencode($cat) . '/?', $start, $total, $kmess);
            $data['form_action'] = '/help/smilies/set/?cat=' . urlencode($cat) . '&page=' . $page;
        }

        return $this->render->render('help::smiles_list', ['data' => $data]);
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
