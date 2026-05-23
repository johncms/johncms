<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Notifications\Application\UseCases\SaveSettingsUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class SettingsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private SaveSettingsUseCase $saveSettingsUseCase,
    ) {
        $this->controllerContext->initModule('notifications');
    }

    public function __invoke(): string
    {
        $title = __('Settings');

        $this->navChain->add(__('Notifications'), '/notifications/');
        $this->navChain->add($title, '/notifications/settings/');

        if ($this->request->getMethod() === 'POST') {
            $showForumUnread = (bool) $this->request->getPost('show_forum_unread', 0, FILTER_VALIDATE_INT);
            $this->saveSettingsUseCase->execute($showForumUnread);
            $_SESSION['message'] = __('Settings saved!');
            header('Location: /notifications/settings/');
            exit;
        }

        $message = '';
        if (! empty($_SESSION['message'])) {
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
        }

        $defaultSettings = ['show_forum_unread' => true];
        $currentSettings = array_merge($defaultSettings, ($this->currentUser->notification_settings ?? []));

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render('notifications::settings', [
            'data' => [
                'title'            => $title,
                'page_title'       => $title,
                'back_url'         => '/notifications/',
                'form_action'      => '/notifications/settings/',
                'message'          => $message,
                'current_settings' => $currentSettings,
            ],
        ]);
    }
}
