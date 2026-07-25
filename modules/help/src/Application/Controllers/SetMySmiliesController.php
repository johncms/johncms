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
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetMySmiliesController
{
    private const USER_SMILIES_MAX = 20;

    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('help');
    }

    public function __invoke(): Response
    {
        if (! $this->currentUser->isValid()) {
            return new Response($this->render->render('system::pages/result', [
                'title'         => __('Access denied'),
                'type'          => 'alert-danger',
                'message'       => __('You are not logged in'),
                'back_url'      => '/help/smilies/',
                'back_url_name' => __('Back'),
            ]), Response::HTTP_FORBIDDEN);
        }

        $adm = (bool) $this->request->queryParam('adm');
        $cat = trim($this->request->queryParam('cat', ''));
        $page = max(1, $this->request->queryInt('page', 1));

        $post = $this->request->request->all();
        $isAdd = isset($post['add']);
        $isDelete = isset($post['delete']);

        $invalidRequest = ($adm && ! $this->currentUser->rights)
            || ($isAdd && ! $adm && ! $cat)
            || ($isDelete && empty($post['delete_sm']))
            || ($isAdd && empty($post['add_sm']));

        if ($invalidRequest) {
            return new RedirectResponse('/help/smilies/my/');
        }

        $smilies = is_array($this->currentUser->smileys) ? $this->currentUser->smileys : [];

        if ($isDelete) {
            $smilies = array_values(array_diff($smilies, (array) $post['delete_sm']));
        }

        if ($isAdd) {
            $smilies = array_values(array_unique(array_merge($smilies, (array) $post['add_sm'])));
        }

        if (count($smilies) > self::USER_SMILIES_MAX) {
            $smilies = array_slice($smilies, 0, self::USER_SMILIES_MAX);
        }

        User::query()->where('id', $this->currentUser->id)->update(['smileys' => serialize($smilies)]);

        $pageSuffix = $page > 1 ? '?page=' . $page : '';

        if ($isDelete || isset($post['clean'])) {
            return new RedirectResponse('/help/smilies/my/' . $pageSuffix);
        }

        if ($adm) {
            return new RedirectResponse('/help/smilies/admin/' . $pageSuffix);
        }

        return new RedirectResponse('/help/smilies/' . urlencode($cat) . '/' . $pageSuffix);
    }
}
