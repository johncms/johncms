<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Mail\Application\UseCases\BlockUserUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class BlockUserController
{
    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private BlockUserUseCase $blockUserUseCase,
    ) {
    }

    public function __invoke(Request $request, int $userId): RedirectResponse|ViewResponse
    {
        if ($request->getMethod() === 'POST') {
            try {
                $this->blockUserUseCase->execute($userId);
                $this->session->flash('message', __('User blocked successfully'));
                $this->session->flash('message_type', 'success');
            } catch (\InvalidArgumentException $e) {
                $this->session->flash('message', $e->getMessage());
                $this->session->flash('message_type', 'error');
            }

            return new RedirectResponse('/mail/blocklist');
        }

        // Show confirmation page
        $this->navChain->add(__('My Account'), '/profile/account');
        $this->navChain->add(__('Mail'), '/mail/incoming');
        $this->navChain->add(__('Blacklist'), '/mail/blocklist');
        $this->navChain->add(__('Block user'));

        $data = [
            'form_action'     => '/mail/block/' . $userId,
            'message'         => __('You really want to block contact?'),
            'back_url'        => '/mail/blocklist',
            'submit_btn_name' => __('Block'),
        ];

        return new ViewResponse(
            '@mail/public/confirm.twig',
            [
                'title'      => __('Block user'),
                'page_title' => __('Block user'),
                'data'       => $data,
            ]
        );
    }
}
