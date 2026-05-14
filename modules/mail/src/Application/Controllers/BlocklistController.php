<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\UseCases\BlockUserUseCase;
use Johncms\Modules\Mail\Application\UseCases\UnblockUserUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class BlocklistController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private BlockUserUseCase $blockUserUseCase,
        private UnblockUserUseCase $unblockUserUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function block(int $userId): string
    {
        if ($this->request->getMethod() === 'POST') {
            try {
                $this->blockUserUseCase->execute($userId);
                $_SESSION['message'] = __('User blocked successfully');
                $_SESSION['message_type'] = 'success';
            } catch (\InvalidArgumentException $e) {
                $_SESSION['message'] = $e->getMessage();
                $_SESSION['message_type'] = 'error';
            }

            header('Location: /mail/blocklist');
            exit;
        }

        // Show confirmation page
        $this->navChain->add(__('My Account'), '/profile/?act=office');
        $this->navChain->add(__('Mail'), '/mail/');
        $this->navChain->add(__('Blacklist'), '/mail/blocklist');
        $this->navChain->add(__('Block user'));

        $this->render->addData([
            'title' => __('Block user'),
            'page_title' => __('Block user'),
        ]);

        $data = [
            'form_action'     => '/mail/block/' . $userId,
            'message'         => __('You really want to block contact?'),
            'back_url'        => '/mail/blocklist',
            'submit_btn_name' => __('Block'),
        ];

        return $this->render->render(
            'mail::confirm',
            [
                'data'       => $data,
            ]
        );
    }

    public function unblock(int $userId): string
    {
        if ($this->request->getMethod() === 'POST') {
            $this->unblockUserUseCase->execute($userId);
            $_SESSION['message'] = __('User unblocked successfully');
            $_SESSION['message_type'] = 'success';

            header('Location: /mail/blocklist');
            exit;
        }

        // Show confirmation page
        $this->navChain->add(__('My Account'), '/profile/?act=office');
        $this->navChain->add(__('Mail'), '/mail/');
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
