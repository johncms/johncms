<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Comments;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetGuestbookContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\MarkGuestbookReadUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class GuestbookController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetGuestbookContextUseCase $getGuestbookContextUseCase,
        private MarkGuestbookReadUseCase $markGuestbookReadUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id): string
    {
        try {
            $profileUser = $this->getGuestbookContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('Guestbook'),
                    'type'    => 'alert-danger',
                    'message' => $e->getMessage(),
                ]
            );
        }

        $this->navChain->add(__('Profile') . ': ' . $profileUser->name, '/profile/' . $profileUser->id);
        $this->navChain->add(__('Guestbook'));

        // Set the globals required by the legacy Comments class
        global $mod, $start;
        $mod = $this->request->getQuery('mod', '');
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $start = isset($_REQUEST['page'])
            ? ($page - 1) * (int) $this->currentUser->config->kmess
            : (isset($_GET['start']) ? abs((int) $_GET['start']) : 0);

        // Reset the unread counter only when the owner simply views the guestbook (not during reply/edit/delete)
        if (! $mod) {
            $this->markGuestbookReadUseCase->execute($profileUser->id);
        }

        $meta = new PageMeta(__('Guestbook') . ': ' . $profileUser->name, $page);

        // Comments renders a complete page (including layout) internally, so capture and return as-is.
        ob_start();
        new Comments([
            'comments_table'      => 'cms_users_guestbook',
            'object_table'        => 'users',
            'script'              => '/profile/' . $profileUser->id . '/guestbook',
            'sub_id'              => $profileUser->id,
            'owner'               => $profileUser->id,
            'owner_delete'        => true,
            'owner_reply'         => true,
            'title'               => $meta->title,
            'page_title'          => __('Guestbook'),
            'templates_namespace' => 'system',
            'back_url'            => '/profile/' . $profileUser->id,
        ]);
        return (string) ob_get_clean();
    }
}
