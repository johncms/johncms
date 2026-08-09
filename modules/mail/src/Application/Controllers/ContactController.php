<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Mail\Application\UseCases\GetContactListUseCase;
use Johncms\NavChain;

final readonly class ContactController
{
    public function __construct(
        private NavChain $navChain,
        private GetContactListUseCase $getContactListUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $pagination = $this->paginationFactory->create($this->getContactListUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->getContactListUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Contacts'), '/mail/contacts');

        $pageTitle = __('Contacts');
        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());
        return new ViewResponse(
            '@mail/public/contacts.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'data' => [
                    'items' => $result->items,
                    'total' => $pagination->getTotal(),
                    'pagination' => $pagination->render(),
                    'filters' => $result->filters,
                    'back_url' => $result->backUrl,
                    'nav_active' => 'contacts',
                ],
            ]
        );
    }
}
