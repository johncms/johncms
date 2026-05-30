<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\ClearConversationUseCase;
use Johncms\Modules\Mail\Application\UseCases\GetClearConversationContextUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ClearConversationController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetClearConversationContextUseCase $getClearConversationContextUseCase,
        private ClearConversationUseCase $clearConversationUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function confirm(int $id): string
    {
        try {
            $context = $this->getClearConversationContextUseCase->execute($id);
        } catch (UserNotFoundException) {
            return $this->renderError();
        }

        $this->navChain->add(__('Mail'), '/mail/');
        $this->navChain->add(__('Clear messages'));

        $data = [
            'form_action'     => '/mail/clear/' . $id,
            'message'         => __('Confirm the deletion of messages'),
            'back_url'        => $context->backUrl,
            'submit_btn_name' => __('Delete'),
        ];

        return $this->render->render(
            'mail::confirm',
            [
                'title'      => __('Clear messages'),
                'page_title' => __('Clear messages'),
                'data'       => $data,
            ]
        );
    }

    public function clear(int $id): string
    {
        try {
            $this->clearConversationUseCase->execute($id);
        } catch (UserNotFoundException) {
            return $this->renderError();
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Clear messages'),
                'type'          => 'alert-success',
                'message'       => __('Messages are deleted'),
                'back_url'      => '/mail/write/' . $id,
                'back_url_name' => __('Back'),
            ]
        );
    }

    private function renderError(): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'    => __('Clear messages'),
                'type'     => 'alert-danger',
                'message'  => __('User does not exists'),
                'back_url' => '/mail/',
            ]
        );
    }
}
