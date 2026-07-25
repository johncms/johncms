<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Comments;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetPhotoCommentsContextUseCase;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class PhotoCommentsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetPhotoCommentsContextUseCase $useCase,
        private AlbumPhotoRepositoryInterface $photoRepository,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(int $img): string
    {
        try {
            $context = $this->useCase->execute($img);
        } catch (AlbumPhotoNotFoundException) {
            return $this->renderError(__('Wrong data'));
        } catch (AlbumAccessDeniedException | AlbumPasswordRequiredException $e) {
            return $this->renderError(__('Access forbidden'), '/album/user/' . $e->ownerId);
        }

        // Reset the unread mark when the owner opens the comments.
        if ($this->currentUser->id === $context->ownerId && $context->ownerUnread) {
            $this->photoRepository->setUnreadComments($img, false);
        }

        $this->navChain->add(__('Albums'), '/album');
        $userAlbumsLabel = $context->ownerId === $this->currentUser->id ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $context->ownerId);
        $this->navChain->add($context->albumName, '/album/' . $context->albumId);
        $this->navChain->add(__('Photo'), '/album/photo/' . $context->photoId);
        $this->navChain->add(__('Comments'));

        // Globals consumed by the legacy Comments class.
        global $mod, $start;
        $mod = $this->request->queryParam('mod', '');
        $page = max(1, $this->request->queryInt('page', 1));
        $start = isset($_REQUEST['page'])
            ? ($page - 1) * (int) $this->currentUser->config->kmess
            : (isset($_GET['start']) ? abs((int) $_GET['start']) : 0);

        $meta = new PageMeta(__('Comments'), $page);

        // Comments renders a complete page (including layout) internally, so capture and return as-is.
        ob_start();
        $comm = new Comments([
            'comments_table'      => 'cms_album_comments',
            'object_table'        => 'cms_album_files',
            'script'              => '/album/photo/' . $img . '/comments',
            'sub_id'              => $img,
            'owner'               => $context->ownerId,
            'owner_delete'        => true,
            'owner_reply'         => true,
            'owner_edit'          => false,
            'title'               => $meta->title,
            'page_title'          => __('Comments'),
            'templates_namespace' => 'system',
            'back_url'            => '/album/' . $context->albumId,
        ]);
        $html = (string) ob_get_clean();

        // Flag unread comments for the owner when someone else adds one.
        if ($comm->added && $this->currentUser->id !== $context->ownerId) {
            $this->photoRepository->setUnreadComments($img, true);
        }

        return $html;
    }

    private function renderError(string $message, string $backUrl = ''): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Comments'),
                'type'          => 'alert-danger',
                'message'       => $message,
                'back_url'      => $backUrl,
                'back_url_name' => $backUrl !== '' ? __('Album List') : '',
            ]
        );
    }
}
