<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\Exceptions\ContactNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\DeleteContactUseCase;
use Johncms\Modules\Mail\Application\UseCases\GetDeleteContactContextUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class DeleteContactController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetDeleteContactContextUseCase $getDeleteContactContextUseCase,
        private DeleteContactUseCase $deleteContactUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function confirm(int $id): string
    {
        try {
            $context = $this->getDeleteContactContextUseCase->execute($id);
        } catch (ContactNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Contacts'), '/mail/contacts');
        $this->navChain->add(__('Delete contact'));

        $data = [
            'form_action'     => '/mail/delete-contact/' . $id,
            'message'         => __('When you delete a contact is deleted and all correspondence with him.<br>Are you sure you want to delete?'),
            'back_url'        => $context->backUrl,
            'submit_btn_name' => __('Delete'),
        ];

        return $this->render->render(
            'mail::confirm',
            [
                'title'      => __('Delete'),
                'page_title' => __('Delete'),
                'data'       => $data,
            ]
        );
    }

    public function delete(int $id): string
    {
        try {
            $this->deleteContactUseCase->execute($id);
        } catch (ContactNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Delete'),
                'type'          => 'alert-success',
                'message'       => __('Contact deleted'),
                'back_url'      => '/mail/contacts',
                'back_url_name' => __('Back'),
            ]
        );
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Delete'),
                'type'    => 'alert-danger',
                'message' => $message,
                'back_url' => '/mail/contacts',
            ]
        );
    }
}
