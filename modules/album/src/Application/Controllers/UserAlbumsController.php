<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumOwnerNotFoundException;
use Johncms\Modules\Album\Application\UseCases\GetUserAlbumsUseCase;
use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class UserAlbumsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetUserAlbumsUseCase $useCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(int $id): string
    {
        // Leaving the album list clears any unlocked password-protected album session.
        unset($_SESSION['ap']);

        try {
            $result = $this->useCase->execute($id);
        } catch (AlbumOwnerNotFoundException $e) {
            return $this->render->render(
                'system::pages/result',
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
                'description'  => $this->tools->checkout($album->description, 0, 0),
                'count_photos' => $album->photos_count,
                'album_url'    => '/album/' . $album->id,
                'has_edit'     => $result->canManage,
            ];

            if ($result->canManage) {
                $row['up_url'] = '/album/sort?mod=up&al=' . $album->id . '&user=' . $owner->id;
                $row['down_url'] = '/album/sort?mod=down&al=' . $album->id . '&user=' . $owner->id;
                $row['edit_url'] = '/album/edit?al=' . $album->id . '&user=' . $owner->id;
                $row['delete_url'] = '/album/delete?al=' . $album->id . '&user=' . $owner->id;
            }

            $albums[] = $row;
        }

        $isSelf = $owner->id === $this->currentUser->id;
        $title = $isSelf ? __('Your albums') : __('User albums:') . ' ' . $owner->name;

        $this->navChain->add(__('Albums'), '/album');
        $this->navChain->add($isSelf ? __('Your albums') : __('User albums'));

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::list',
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
                'create_url'   => $result->canCreate ? '/album/edit?user=' . $owner->id : '',
            ]
        );
    }
}
