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
            return $this->save($request, '/notifications/settings/');
        }

        return new ViewResponse(
            '@notifications/public/settings.twig',
            [
                'title'            => $title,
                'page_title'       => $title,
                'back_url'         => '/notifications/',
                'form_action'      => '/notifications/settings/',
                'message'          => (string) $this->session->getFlash('message'),
                'current_settings' => $this->currentSettings(),
            ]
        );
    }

    public function admin(Request $request): Response|ViewResponse
    {
        $title = d__('notifications', 'Notification settings');

        $this->navChain->add($title, '/admin/notifications/settings');

        if ($request->getMethod() === 'POST') {
            return $this->save($request, '/admin/notifications/settings');
        }

        return new ViewResponse(
            '@notifications/admin/settings.twig',
            [
                'title'            => $title,
                'page_title'       => $title,
                'form_action'      => '/admin/notifications/settings',
                'message'          => (string) $this->session->getFlash('message'),
                'current_settings' => $this->currentSettings(),
            ]
        );
    }

    private function save(Request $request, string $returnUrl): RedirectResponse
    {
        $this->saveSettingsUseCase->execute((bool) $request->bodyInt('show_forum_unread'));
        $this->session->flash('message', d__('notifications', 'Settings saved!'));

        return new RedirectResponse($returnUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function currentSettings(): array
    {
        return array_merge(['show_forum_unread' => true], ($this->currentUser->user()->notification_settings ?? []));
    }
}
