<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\UseCases\BlockUserUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class BlockUserController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private BlockUserUseCase $blockUserUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(int $userId): string
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
        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
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
}
