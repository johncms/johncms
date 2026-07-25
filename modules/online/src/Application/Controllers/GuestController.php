<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Forum\Application\Services\ForumVisitorPlaceFormatter;
use Johncms\Modules\Online\Application\FiltersBuilder;
use Johncms\Modules\Online\Application\UseCases\GetOnlineGuestsUseCase;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
use Psr\Container\NotFoundExceptionInterface;

final readonly class GuestController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Translator $translator,
        private FiltersBuilder $filtersBuilder,
        private GetOnlineGuestsUseCase $onlineGuests,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('online');
    }

    public function __invoke(): string
    {
        $pageTitle = __('Guests');

        $this->navChain->add(__('Online'), '/online/');

        $forumPlaceFormatter = null;
        // The forum module is optional: when it is not installed the container has no
        // ForumVisitorPlaceFormatter and the online list simply shows no forum locations.
        // Only that absence is tolerated — a forum that is installed but misconfigured must
        // surface instead of being swallowed by a bare catch-all.
        try {
            $forumPlaceFormatter = di(ForumVisitorPlaceFormatter::class);
            $this->translator->addTranslationDomain('forum', MODULES_PATH . 'forum/locale', false);
        } catch (NotFoundExceptionInterface) {
        }

        $filters = $this->filtersBuilder->build('guest');

        $pagination = $this->paginationFactory->create($this->onlineGuests->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($pageTitle . ' — ' . __('Online'), $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $total = $pagination->getTotal();
        $items = $total > 0
            ? $this->onlineGuests->getPage($pagination->getPerPage(), $pagination->getOffset(), $forumPlaceFormatter)
            : [];

        return $this->render->render('online::users', [
            'data' => [
                'filters'    => $filters,
                'pagination' => $pagination->render(),
                'total'      => $total,
                'items'      => $items,
            ],
        ]);
    }
}
