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
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class SetMySmiliesController
{
    private const USER_SMILEYS_MAX = 20;

    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
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

        $adm = (bool) $this->request->getQuery('adm', false);
        $cat = trim((string) $this->request->getQuery('cat', ''));
        $page = max(1, (int) $this->request->getQuery('page', 1));

        $post = $this->request->getParsedBody();
        $isAdd = isset($post['add']);
        $isDelete = isset($post['delete']);

        $invalidRequest = ($adm && ! $this->currentUser->rights)
            || ($isAdd && ! $adm && ! $cat)
            || ($isDelete && empty($post['delete_sm']))
            || ($isAdd && empty($post['add_sm']));

        if ($invalidRequest) {
            header('Location: /help/smilies/my/');
            exit;
        }

        $smileys = is_array($this->currentUser->smileys) ? $this->currentUser->smileys : [];

        if ($isDelete) {
            $smileys = array_values(array_diff($smileys, (array) $post['delete_sm']));
        }

        if ($isAdd) {
            $smileys = array_values(array_unique(array_merge($smileys, (array) $post['add_sm'])));
        }

        if (count($smileys) > self::USER_SMILEYS_MAX) {
            $smileys = array_slice($smileys, 0, self::USER_SMILEYS_MAX);
        }

        User::query()->where('id', $this->currentUser->id)->update(['smileys' => serialize($smileys)]);

        $pageSuffix = $page > 1 ? '?page=' . $page : '';

        if ($isDelete || isset($post['clean'])) {
            header('Location: /help/smilies/my/' . $pageSuffix);
        } elseif ($adm) {
            header('Location: /help/smilies/admin/' . $pageSuffix);
        } else {
            header('Location: /help/smilies/' . urlencode($cat) . '/' . $pageSuffix);
        }
        exit;
    }
}
