<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\Exceptions\MessageNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\DeleteMessageUseCase;
use Johncms\Modules\Mail\Application\UseCases\GetDeleteMessageContextUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class DeleteMessageController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetDeleteMessageContextUseCase $getDeleteMessageContextUseCase,
        private DeleteMessageUseCase $deleteMessageUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function confirm(int $id): string
    {
        try {
            $context = $this->getDeleteMessageContextUseCase->execute($id);
        } catch (MessageNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        $this->navChain->add(d__('mail', 'Messages'), '/mail/');
        $this->navChain->add(d__('mail', 'Delete message'));

        $data = [
            'form_action'     => '/mail/delete/' . $id,
            'message'         => d__('mail', 'You really want to remove the message?'),
            'back_url'        => $context->backUrl,
            'submit_btn_name' => d__('mail', 'Delete'),
        ];

        return $this->render->render(
            'mail::confirm',
            [
                'title'      => d__('mail', 'Deleting messages'),
                'page_title' => d__('mail', 'Deleting messages'),
                'data'       => $data,
            ]
        );
    }

    public function delete(int $id): string
    {
        try {
            $this->deleteMessageUseCase->execute($id);
        } catch (MessageNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => d__('mail', 'Deleting messages'),
                'type'          => 'alert-success',
                'message'       => d__('mail', 'Message deleted'),
                'back_url'      => '/mail/',
                'back_url_name' => d__('mail', 'Back'),
            ]
        );
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => d__('mail', 'Deleting messages'),
                'type'    => 'alert-danger',
                'message' => $message,
                'back_url' => '/mail/',
            ]
        );
    }
}
