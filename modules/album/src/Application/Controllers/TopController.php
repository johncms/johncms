<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Album\Application\UseCases\GetTopUseCase;
use Johncms\Modules\Album\Domain\Enums\TopFilter;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class TopController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetTopUseCase $useCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(?string $filter = null): string
    {
        $topFilter = TopFilter::fromSlug($filter);

        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $result = $this->useCase->execute($topFilter, $page, $perPage);

        $title = $topFilter->title();
        $this->navChain->add(__('Albums'), '/album');
        $this->navChain->add($title);

        $slug = $topFilter->slug();
        $baseUrl = '/album/top' . ($slug !== null ? '/' . $slug : '');
        $total = $result->photos->total();

        $meta = new PageMeta($title, $page);
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::top',
            [
                'photos'     => $result->photos->items(),
                'total'      => $total,
                'per_page'   => $perPage,
                'pagination' => $this->tools->displayPagination(
                    $baseUrl . '?',
                    ($page - 1) * $perPage,
                    $total,
                    $perPage
                ),
            ]
        );
    }
}
