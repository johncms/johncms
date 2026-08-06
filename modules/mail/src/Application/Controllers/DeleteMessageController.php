<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Mail\Application\Exceptions\MessageNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\DeleteMessageUseCase;
use Johncms\Modules\Mail\Application\UseCases\GetDeleteMessageContextUseCase;
use Johncms\NavChain;

final readonly class DeleteMessageController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private GetDeleteMessageContextUseCase $getDeleteMessageContextUseCase,
        private DeleteMessageUseCase $deleteMessageUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function confirm(int $id): ViewResponse
    {
        try {
            $context = $this->getDeleteMessageContextUseCase->execute($id);
        } catch (MessageNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Delete message'));

        $data = [
            'form_action'     => '/mail/delete/' . $id,
            'message'         => __('You really want to remove the message?'),
            'back_url'        => $context->backUrl,
            'submit_btn_name' => __('Delete'),
        ];

        return new ViewResponse(
            '@mail/public/confirm.twig',
            [
                'title'      => __('Deleting messages'),
                'page_title' => __('Deleting messages'),
                'data'       => $data,
            ]
        );
    }

    public function delete(int $id): ViewResponse
    {
        try {
            $this->deleteMessageUseCase->execute($id);
        } catch (MessageNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Deleting messages'),
                'type'          => 'alert-success',
                'message'       => __('Message deleted'),
                'back_url'      => '/mail/incoming',
                'back_url_name' => __('Back'),
            ]
        );
    }

    private function renderError(string $message): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => __('Deleting messages'),
                'type'    => 'alert-danger',
                'message' => $message,
                'back_url' => '/mail/incoming',
            ]
        );
    }
}
