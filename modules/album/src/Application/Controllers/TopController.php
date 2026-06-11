<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Album\Application\UseCases\GetTopUseCase;
use Johncms\Modules\Album\Domain\Enums\TopFilter;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class TopController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetTopUseCase $useCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(?string $filter = null): string
    {
        $topFilter = TopFilter::fromSlug($filter);

        $title = $topFilter->title();
        $this->navChain->add(__('Albums'), '/album');
        $this->navChain->add($title);

        $pagination = $this->paginationFactory->create($this->useCase->count($topFilter));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $total = $pagination->getTotal();
        $photos = $total > 0
            ? $this->useCase->getPage($topFilter, $pagination->getPerPage(), $pagination->getOffset())
            : [];

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::top',
            [
                'photos'     => $photos,
                'total'      => $total,
                'per_page'   => $pagination->getPerPage(),
                'pagination' => $pagination->render(),
            ]
        );
    }
}
