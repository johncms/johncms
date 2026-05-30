<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\Exceptions\CannotAddYourselfException;
use Johncms\Modules\Mail\Application\Exceptions\ContactAlreadyExistsException;
use Johncms\Modules\Mail\Application\UseCases\AddContactUseCase;
use Johncms\Modules\Mail\Application\UseCases\GetAddContactContextUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class AddContactController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetAddContactContextUseCase $getAddContactContextUseCase,
        private AddContactUseCase $addContactUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function confirm(int $id): string
    {
        try {
            $contactUser = $this->getAddContactContextUseCase->execute($id);
        } catch (UserNotFoundException | CannotAddYourselfException | ContactAlreadyExistsException $e) {
            return $this->renderError($e->getMessage());
        }

        $this->navChain->add(__('My Account'), '/profile/?act=office');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Contacts'), '/mail/contacts');
        $this->navChain->add(__('Add Contact'));

        $data = [
            'form_action'     => '/mail/add/' . $id,
            'message'         => __('You really want to add contact?'),
            'back_url'        => '/profile/?user=' . $id,
            'submit_btn_name' => __('Add'),
        ];

        return $this->render->render(
            'mail::confirm',
            [
                'title'      => __('Add Contact'),
                'page_title' => __('Add Contact'),
                'data'       => $data,
            ]
        );
    }

    public function add(int $id): string
    {
        try {
            $this->addContactUseCase->execute($id);
        } catch (UserNotFoundException | CannotAddYourselfException | ContactAlreadyExistsException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Add Contact'),
                'type'          => 'alert-success',
                'message'       => __('User has been added to your contact list'),
                'back_url'      => '/mail/contacts',
                'back_url_name' => __('Continue'),
            ]
        );
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Add Contact'),
                'type'    => 'alert-danger',
                'message' => $message,
                'back_url' => '/mail/contacts',
            ]
        );
    }
}
