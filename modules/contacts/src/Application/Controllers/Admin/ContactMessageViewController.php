<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers\Admin;

use Johncms\Modules\Contacts\Application\UseCases\MarkContactMessageProcessedUseCase;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;

final readonly class ContactMessageViewController
{
    private const URL = '/admin/contacts/messages';

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private ContactMessageRepositoryInterface $repository,
        private MarkContactMessageProcessedUseCase $markProcessed,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $message = $this->repository->findById($id);
        if ($message === null) {
            redirect(self::URL);
        }

        if ($request->getMethod() === 'POST') {
            $this->markProcessed->execute($message);
            $this->session->flash('success_message', __('The message is marked as processed'));
            redirect(self::URL . '/' . $message->id);
        }

        $title = __('Message from %s', $message->name);
        $this->navChain->add(__('Contacts'), '/admin/contacts');
        $this->navChain->add(__('Contact messages'), self::URL);
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

        return new ViewResponse(
            '@contacts/admin/message.twig',
            [
                'title'       => $title,
                'page_title'  => $title,
                'module_menu' => ['contacts' => true],
            ] + [
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
}
