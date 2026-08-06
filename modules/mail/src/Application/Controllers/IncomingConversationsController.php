<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Mail\Application\UseCases\GetIncomingConversationsUseCase;
use Johncms\NavChain;

final readonly class IncomingConversationsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private GetIncomingConversationsUseCase $getIncomingConversationsUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(): ViewResponse
    {
        $pagination = $this->paginationFactory->create($this->getIncomingConversationsUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->getIncomingConversationsUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');

        $pageTitle = __('Incoming messages');
        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());
        return new ViewResponse(
            '@mail/public/conversations.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'data' => [
                    'items' => $result->items->map(fn ($item) => $item->toArray())->all(),
                    'total' => $pagination->getTotal(),
                    'pagination' => $pagination->render(),
                    'back_url' => $result->backUrl,
                    'nav_active' => 'incoming',
                ],
            ]
        );
    }
}
