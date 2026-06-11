<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Forum\Application\Services\ForumVisitorPlaceFormatter;
use Johncms\Modules\Online\Application\FiltersBuilder;
use Johncms\Modules\Online\Application\UseCases\GetOnlineUsersUseCase;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
use Throwable;

final readonly class IndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Translator $translator,
        private FiltersBuilder $filtersBuilder,
        private GetOnlineUsersUseCase $onlineUsers,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('online');
    }

    public function __invoke(): string
    {
        $pageTitle = __('Who is online?');

        $this->navChain->add(__('Online'), '/online/');

        $forumPlaceFormatter = null;
        try {
            $forumPlaceFormatter = di(ForumVisitorPlaceFormatter::class);
            $this->translator->addTranslationDomain('forum', MODULES_PATH . 'forum/locale', false);
        } catch (Throwable) {
        }

        $filters = $this->filtersBuilder->build('users');

        $pagination = $this->paginationFactory->create($this->onlineUsers->count());

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
            ? $this->onlineUsers->getPage($pagination->getPerPage(), $pagination->getOffset(), $forumPlaceFormatter)
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
