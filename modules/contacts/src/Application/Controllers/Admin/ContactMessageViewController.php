<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Contacts\Application\UseCases\MarkContactMessageProcessedUseCase;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ContactMessageViewController
{
    private const URL = '/admin/contacts/messages';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ContactMessageRepositoryInterface $repository,
        private MarkContactMessageProcessedUseCase $markProcessed,
    ) {
        $this->controllerContext->initModule('contacts');
    }

    public function __invoke(int $id): string
    {
        $message = $this->repository->findById($id);
        if ($message === null) {
            redirect(self::URL);
        }

        if ($this->request->getMethod() === 'POST' && $this->isCsrfValid()) {
            $this->markProcessed->execute($message);
            $_SESSION['success_message'] = __('The message is marked as processed');
            redirect(self::URL . '/' . $message->id);
        }

        $title = __('Message from %s', $message->name);
        $this->navChain->add(__('Contacts'), '/admin/contacts');
        $this->navChain->add(__('Contact messages'), self::URL);
        $this->navChain->add($title);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $this->render->addData(
            [
                'title'       => $title,
                'page_title'  => $title,
                'module_menu' => ['contacts' => true],
            ]
        );

        return $this->render->render(
            'contacts::admin/messages/view',
            [
                'message'         => $message,
                'is_processed'    => $message->status === ContactMessageStatus::Processed,
                'created_at'      => $message->created_at?->format('d.m.Y H:i') ?? '',
                'processed_at'    => $message->processed_at?->format('d.m.Y H:i') ?? '',
                'back_url'        => self::URL,
                'process_url'     => self::URL . '/' . $message->id,
                'delete_url'      => self::URL . '/' . $message->id . '/delete',
                'success_message' => $successMessage,
            ]
        );
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
