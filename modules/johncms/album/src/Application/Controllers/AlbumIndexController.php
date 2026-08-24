<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Album\Application\UseCases\GetAlbumIndexUseCase;
use Johncms\NavChain;

final readonly class AlbumIndexController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private GetAlbumIndexUseCase $getAlbumIndexUseCase,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $data = $this->getAlbumIndexUseCase->execute();

        $title = __('Albums');
        $this->navChain->add($title, '/album');

        return new ViewResponse(
            '@album/public/index.twig',
            [
                'title'         => $title,
                'page_title'    => $title,
                'data'          => $data,
                'my_albums_url' => '/album/user/' . $this->currentUser->id(),
            ]
        );
    }
}
