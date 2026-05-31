<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\UseCases\UnblockUserUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class UnblockUserController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private UnblockUserUseCase $unblockUserUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(int $userId): string
    {
        if ($this->request->getMethod() === 'POST') {
            $this->unblockUserUseCase->execute($userId);
            $_SESSION['message'] = __('User unblocked successfully');
            $_SESSION['message_type'] = 'success';

            header('Location: /mail/blocklist');
            exit;
        }

        // Show confirmation page
        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Blacklist'), '/mail/blocklist');
        $this->navChain->add(__('Unblock user'));

        $this->render->addData([
            'title' => __('Unblock user'),
            'page_title' => __('Unblock user'),
        ]);

        $data = [
            'form_action'     => '/mail/unblock/' . $userId,
            'message'         => __('You really want to unblock contact?'),
            'back_url'        => '/mail/blocklist',
            'submit_btn_name' => __('Unblock'),
        ];

        return $this->render->render(
            'mail::confirm',
            [
                'data'       => $data,
            ]
        );
    }
}
