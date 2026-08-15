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

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetMySmiliesController
{
    private const USER_SMILIES_MAX = 20;

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
    ) {
    }

    public function __invoke(Request $request): Response|ViewResponse
    {
        if (! $this->currentUser->isValid()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Access denied'),
                    'type'          => 'alert-danger',
                    'message'       => __('You are not logged in'),
                    'back_url'      => '/help/smilies/',
                    'back_url_name' => __('Back'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $adm = (bool) $request->queryParam('adm');
        $cat = trim($request->queryParam('cat', ''));
        $page = max(1, $request->queryInt('page', 1));

        $post = $request->request->all();
        $isAdd = isset($post['add']);
        $isDelete = isset($post['delete']);

        $invalidRequest = ($adm && ! $this->accessChecker->allows(CorePermissions::SMILIES_ADMIN_USE))
            || ($isAdd && ! $adm && ! $cat)
            || ($isDelete && empty($post['delete_sm']))
            || ($isAdd && empty($post['add_sm']));

        if ($invalidRequest) {
            return new RedirectResponse('/help/smilies/my/');
        }

        $smilies = is_array($this->currentUser->user()->smileys) ? $this->currentUser->user()->smileys : [];

        if ($isDelete) {
            $smilies = array_values(array_diff($smilies, (array) $post['delete_sm']));
        }

        if ($isAdd) {
            $smilies = array_values(array_unique(array_merge($smilies, (array) $post['add_sm'])));
        }

        if (count($smilies) > self::USER_SMILIES_MAX) {
            $smilies = array_slice($smilies, 0, self::USER_SMILIES_MAX);
        }

        User::query()->where('id', $this->currentUser->id())->update(['smileys' => serialize($smilies)]);

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
