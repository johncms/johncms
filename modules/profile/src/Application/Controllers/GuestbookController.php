<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Comments;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\PageMeta;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetGuestbookContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\MarkGuestbookReadUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\User;

final readonly class GuestbookController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetGuestbookContextUseCase $getGuestbookContextUseCase,
        private MarkGuestbookReadUseCase $markGuestbookReadUseCase,
    ) {
    }

    // The legacy Comments class prints a whole page of its own, so this action still hands back a
    // string; the error pages it may answer with are views.
    public function __invoke(Request $request, int $id): ViewResponse|string
    {
        try {
            $profileUser = $this->getGuestbookContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Guestbook'),
                    'type'    => 'alert-danger',
                    'message' => $e->getMessage(),
                ]
            );
        }

        $this->navChain->add(__('Profile') . ': ' . $profileUser->name, '/profile/' . $profileUser->id);
        $this->navChain->add(__('Guestbook'));

        // Display parameters of the legacy Comments class.
        $mod = $request->queryParam('mod', '');
        $page = max(1, $request->queryInt('page', 1));
        $start = $request->query->has('page')
            ? ($page - 1) * (int) $this->currentUser->config->kmess
            : abs($request->queryInt('start', 0));

        // Reset the unread counter only when the owner simply views the guestbook (not during reply/edit/delete)
        if (! $mod) {
            $this->markGuestbookReadUseCase->execute($profileUser->id);
        }

        $meta = new PageMeta(__('Guestbook') . ': ' . $profileUser->name, $page);

        // Comments renders a complete page (including layout) internally, so capture and return as-is.
        ob_start();
        new Comments([
            'mod'                 => $mod,
            'start'               => $start,
            'comments_table'      => 'cms_users_guestbook',
            'object_table'        => 'users',
            'script'              => '/profile/' . $profileUser->id . '/guestbook',
            'sub_id'              => $profileUser->id,
            'owner'               => $profileUser->id,
            'owner_delete'        => true,
            'owner_reply'         => true,
            'title'               => $meta->title,
            'page_title'          => __('Guestbook'),
            'back_url'            => '/profile/' . $profileUser->id,
        ]);
        return (string) ob_get_clean();
    }
}
