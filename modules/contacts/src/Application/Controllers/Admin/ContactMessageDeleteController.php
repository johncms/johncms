<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers\Admin;

use Johncms\Modules\Contacts\Application\UseCases\DeleteContactMessageUseCase;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

final readonly class ContactMessageDeleteController
{
    private const URL = '/admin/contacts/messages';

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private ContactMessageRepositoryInterface $repository,
        private DeleteContactMessageUseCase $deleteMessage,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $message = $this->repository->findById($id);
        if ($message === null) {
            redirect(self::URL);
        }

        if ($request->getMethod() === 'POST') {
            $validator = new Validator(
                ['csrf_token' => $request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if ($validator->isValid()) {
                $this->deleteMessage->execute($message);
                $this->session->flash('success_message', __('Record deleted'));
                redirect(self::URL);
            }
        }

        $title = __('Delete message');
        $this->navChain->add(__('Contacts'), '/admin/contacts');
        $this->navChain->add(__('Contact messages'), self::URL);
        $this->navChain->add($title);


        return new ViewResponse(
            '@contacts/admin/message-delete-confirm.twig',
            [
                'title'       => $title,
                'page_title'  => $title,
                'module_menu' => ['contacts' => true],
            ] + [
                'message'    => $message,
                'form_action' => self::URL . '/' . $message->id . '/delete',
                'back_url'   => self::URL . '/' . $message->id,
            ]
        );
    }
}
