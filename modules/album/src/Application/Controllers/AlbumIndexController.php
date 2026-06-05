<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\UseCases\GetAlbumIndexUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class AlbumIndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private GetAlbumIndexUseCase $getAlbumIndexUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(): string
    {
        $data = $this->getAlbumIndexUseCase->execute();

        $title = __('Albums');
        $this->navChain->add($title, '/album');

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::index',
            [
                'data'           => $data,
                'my_albums_url'  => '/album/user/' . $this->currentUser->id,
            ]
        );
    }
}
