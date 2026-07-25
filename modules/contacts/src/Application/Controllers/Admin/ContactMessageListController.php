<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Contacts\Application\UseCases\ListContactMessagesUseCase;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;

final readonly class ContactMessageListController
{
    private const URL = '/admin/contacts/messages';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ListContactMessagesUseCase $messages,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('contacts');
    }

    public function __invoke(): string
    {
        $statusParam = $this->request->queryParam('status');
        $status = ContactMessageStatus::tryFromString(is_string($statusParam) ? $statusParam : null);

        $pagination = $this->paginationFactory->create($this->messages->count($status));
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $title = __('Contact messages');
        $this->navChain->add(__('Contacts'), '/admin/contacts');
        $this->navChain->add($title, self::URL);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        $this->render->addData(
            [
                'title'       => $meta->title,
                'page_title'  => $title,
                'module_menu' => ['contacts' => true],
            ]
        );

        return $this->render->render(
            'contacts::admin/messages/index',
            [
                'items'           => $this->messages->getPage($pagination->getPerPage(), $pagination->getOffset(), $status),
                'pagination'      => $pagination->render(),
                'current_status'  => $status?->value ?? '',
                'base_url'        => self::URL,
                'new_count'       => $this->messages->count(ContactMessageStatus::New),
                'success_message' => $successMessage,
            ]
        );
    }
}
