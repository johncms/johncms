<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Modules\Album\Application\Exceptions\AlbumOwnerNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetUserAlbumsUseCase;
use Johncms\NavChain;
use Johncms\Users\User;

final readonly class UserAlbumsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Session $session,
        private NavChain $navChain,
        private User $currentUser,
        private GetUserAlbumsUseCase $useCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(int $id): ViewResponse
    {
        // Leaving the album list clears any unlocked password-protected album session.
        $this->session->remove('ap');

        try {
            $result = $this->useCase->execute($id);
        } catch (AlbumOwnerNotFoundException $e) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Albums'),
                    'type'    => 'alert-danger',
                    'message' => $e->getMessage(),
                ]
            );
        }

        $owner = $result->owner;

        $albums = [];
        $totalPhotos = 0;
        foreach ($result->albums as $album) {
            $totalPhotos += $album->photos_count;

            $row = [
                'name'         => $album->name,
                'description'  => $album->description,
                'count_photos' => $album->photos_count,
                'album_url'    => '/album/' . $album->id,
                'has_edit'     => $result->canManage,
            ];

            if ($result->canManage) {
                $row['up_url'] = '/album/' . $album->id . '/move-up';
                $row['down_url'] = '/album/' . $album->id . '/move-down';
                $row['edit_url'] = '/album/' . $album->id . '/edit';
                $row['delete_url'] = '/album/' . $album->id . '/delete';
            }

            $albums[] = $row;
        }

        $isSelf = $owner->id === $this->currentUser->id;
        $title = $isSelf ? __('Your albums') : __('User albums:') . ' ' . $owner->name;

        $this->navChain->add(__('Albums'), '/album');
        $this->navChain->add($isSelf ? __('Your albums') : __('User albums'));

        return new ViewResponse(
            '@album/public/list.twig',
            [
                'owner'        => [
                    'id'             => $owner->id,
                    'nick'           => $owner->name,
                    'user_is_online' => $owner->is_online,
                    'album_url'      => '/profile/' . $owner->id,
                    'count_albums'   => $result->albums->count(),
                    'count'          => $totalPhotos,
                ],
                'albums'       => $albums,
                'total_photos' => $totalPhotos,
                'create_url'   => $result->canCreate ? '/album/user/' . $owner->id . '/create' : '',
            ]
        );
    }
}
