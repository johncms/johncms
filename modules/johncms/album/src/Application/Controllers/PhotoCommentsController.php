<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Comments;
use Johncms\Http\PageMeta;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetPhotoCommentsContextUseCase;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class PhotoCommentsController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private GetPhotoCommentsContextUseCase $useCase,
        private AlbumPhotoRepositoryInterface $photoRepository,
    ) {
    }

    // The legacy Comments class prints a whole page of its own, so this action still hands
    // back a string; the error pages it may answer with instead are views.
    public function __invoke(Request $request, int $img): ViewResponse|string
    {
        try {
            $context = $this->useCase->execute($img);
        } catch (AlbumPhotoNotFoundException) {
            return $this->renderError(__('Wrong data'));
        } catch (AlbumAccessDeniedException | AlbumPasswordRequiredException $e) {
            return $this->renderError(__('Access forbidden'), '/album/user/' . $e->ownerId);
        }

        // Reset the unread mark when the owner opens the comments.
        if ($this->currentUser->id() === $context->ownerId && $context->ownerUnread) {
            $this->photoRepository->setUnreadComments($img, false);
        }

        $this->navChain->add(__('Albums'), '/album');
        $userAlbumsLabel = $context->ownerId === $this->currentUser->id() ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $context->ownerId);
        $this->navChain->add($context->albumName, '/album/' . $context->albumId);
        $this->navChain->add(__('Photo'), '/album/photo/' . $context->photoId);
        $this->navChain->add(__('Comments'));

        // Display parameters of the legacy Comments class.
        $mod = $request->queryParam('mod', '');
        $page = max(1, $request->queryInt('page', 1));
        $start = $request->query->has('page')
            ? ($page - 1) * (int) $this->currentUser->user()->config->kmess
            : abs($request->queryInt('start', 0));

        $meta = new PageMeta(__('Comments'), $page);

        // Comments renders a complete page (including layout) internally, so capture and return as-is.
        ob_start();
        $comm = new Comments([
            'mod'                 => $mod,
            'start'               => $start,
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
            'back_url'            => '/album/' . $context->albumId,
        ]);
        $html = (string) ob_get_clean();

        // Flag unread comments for the owner when someone else adds one.
        if ($comm->added && $this->currentUser->id() !== $context->ownerId) {
            $this->photoRepository->setUnreadComments($img, true);
        }

        return $html;
    }

    private function renderError(string $message, string $backUrl = ''): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
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
