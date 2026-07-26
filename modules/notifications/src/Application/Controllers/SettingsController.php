<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Notifications\Application\UseCases\SaveSettingsUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class SettingsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private NavChain $navChain,
        private User $currentUser,
        private SaveSettingsUseCase $saveSettingsUseCase,
    ) {
        $this->controllerContext->initModule('notifications');
    }

    public function __invoke(): Response
    {
        $title = __('Settings');

        $this->navChain->add(__('Notifications'), '/notifications/');
        $this->navChain->add($title, '/notifications/settings/');

        if ($this->request->getMethod() === 'POST') {
            $showForumUnread = (bool) $this->request->bodyInt('show_forum_unread');
            $this->saveSettingsUseCase->execute($showForumUnread);
            $this->session->flash('message', __('Settings saved!'));
            return new RedirectResponse('/notifications/settings/');
        }

        $message = (string) $this->session->getFlash('message');

        $defaultSettings = ['show_forum_unread' => true];
        $currentSettings = array_merge($defaultSettings, ($this->currentUser->notification_settings ?? []));

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return new Response($this->render->render('notifications::settings', [
            'data' => [
                'title'            => $title,
                'page_title'       => $title,
                'back_url'         => '/notifications/',
                'form_action'      => '/notifications/settings/',
                'message'          => $message,
                'current_settings' => $currentSettings,
            ],
        ]));
    }
}
