<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Mail\Application\UseCases\GetOutgoingConversationsUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class OutgoingConversationsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetOutgoingConversationsUseCase $getOutgoingConversationsUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(): string
    {
        $pagination = $this->paginationFactory->create($this->getOutgoingConversationsUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->getOutgoingConversationsUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Sent messages'), '/mail/outgoing');

        $pageTitle = __('Sent messages');
        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'mail::conversations',
            [
                'data' => [
                    'items' => $result->items->map(fn ($item) => $item->toArray())->all(),
                    'total' => $pagination->getTotal(),
                    'pagination' => $pagination->render(),
                    'back_url' => $result->backUrl,
                    'nav_active' => 'outgoing',
                ],
            ]
        );
    }
}
