<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Notifications\Application\UseCases\SaveSettingsUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class SettingsController
{
    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private SaveSettingsUseCase $saveSettingsUseCase,
    ) {
    }

    public function __invoke(Request $request): Response|ViewResponse
    {
        $title = __('Settings');

        $this->navChain->add(__('Notifications'), '/notifications/');
        $this->navChain->add($title, '/notifications/settings/');

        if ($request->getMethod() === 'POST') {
            $showForumUnread = (bool) $request->bodyInt('show_forum_unread');
            $this->saveSettingsUseCase->execute($showForumUnread);
            $this->session->flash('message', __('Settings saved!'));
            return new RedirectResponse('/notifications/settings/');
        }

        $message = (string) $this->session->getFlash('message');

        $defaultSettings = ['show_forum_unread' => true];
        $currentSettings = array_merge($defaultSettings, ($this->currentUser->user()->notification_settings ?? []));

        return new ViewResponse(
            '@notifications/public/settings.twig',
            [
                'title'            => $title,
                'page_title'       => $title,
                'back_url'         => '/notifications/',
                'form_action'      => '/notifications/settings/',
                'message'          => $message,
                'current_settings' => $currentSettings,
            ]
        );
    }
}
