<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers\Admin;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Contacts\Application\UseCases\ListContactMessagesUseCase;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;

final readonly class ContactMessageListController
{
    private const URL = '/admin/contacts/messages';

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private ListContactMessagesUseCase $messages,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $statusParam = $request->queryParam('status');
        $status = ContactMessageStatus::tryFromString(is_string($statusParam) ? $statusParam : null);

        $pagination = $this->paginationFactory->create($this->messages->count($status));
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $title = __('Contact messages');
        $this->navChain->add(__('Contacts'), '/admin/contacts');
        $this->navChain->add($title, self::URL);

        $newCount = $this->messages->count(ContactMessageStatus::New);
        $data = [
            'items'      => $this->messages->getPage($pagination->getPerPage(), $pagination->getOffset(), $status),
            'filters'    => $this->filters($status?->value ?? '', $newCount),
            'pagination' => $pagination->render(),
            'base_url'   => self::URL,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@contacts/admin/live.twig', $data);
        }

        $successMessage = $this->session->getFlash('success_message');
        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse('@contacts/admin/messages.twig', $data + [
            'title'           => $meta->title,
            'page_title'      => $title,
            'module_menu'     => ['contacts' => true],
            'success_message' => $successMessage,
        ]);
    }

    /**
     * @return list<array{title: string, url: string, active: bool, count: int, tone: string}>
     */
    private function filters(string $currentStatus, int $newCount): array
    {
        return [
            [
                'title' => __('All'),
                'url' => self::URL,
                'active' => $currentStatus === '',
                'count' => 0,
                'tone' => 'default',
            ],
            [
                'title' => __('New'),
                'url' => self::URL . '?status=' . ContactMessageStatus::New->value,
                'active' => $currentStatus === ContactMessageStatus::New->value,
                'count' => $newCount,
                'tone' => 'danger',
            ],
            [
                'title' => __('Processed'),
                'url' => self::URL . '?status=' . ContactMessageStatus::Processed->value,
                'active' => $currentStatus === ContactMessageStatus::Processed->value,
                'count' => 0,
                'tone' => 'default',
            ],
        ];
    }
}
