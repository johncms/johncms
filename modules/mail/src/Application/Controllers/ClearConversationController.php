<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\ClearConversationUseCase;
use Johncms\Modules\Mail\Application\UseCases\GetClearConversationContextUseCase;
use Johncms\NavChain;

final readonly class ClearConversationController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private GetClearConversationContextUseCase $getClearConversationContextUseCase,
        private ClearConversationUseCase $clearConversationUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function confirm(int $id): ViewResponse
    {
        try {
            $context = $this->getClearConversationContextUseCase->execute($id);
        } catch (UserNotFoundException) {
            return $this->renderError();
        }

        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Clear messages'));

        $data = [
            'form_action'     => '/mail/clear/' . $id,
            'message'         => __('Confirm the deletion of messages'),
            'back_url'        => $context->backUrl,
            'submit_btn_name' => __('Delete'),
        ];

        return new ViewResponse(
            '@mail/public/confirm.twig',
            [
                'title'      => __('Clear messages'),
                'page_title' => __('Clear messages'),
                'data'       => $data,
            ]
        );
    }

    public function clear(int $id): ViewResponse
    {
        try {
            $this->clearConversationUseCase->execute($id);
        } catch (UserNotFoundException) {
            return $this->renderError();
        }

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Clear messages'),
                'type'          => 'alert-success',
                'message'       => __('Messages are deleted'),
                'back_url'      => '/mail/write/' . $id,
                'back_url_name' => __('Back'),
            ]
        );
    }

    private function renderError(): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'    => __('Clear messages'),
                'type'     => 'alert-danger',
                'message'  => __('User does not exists'),
                'back_url' => '/mail/incoming',
            ]
        );
    }
}
