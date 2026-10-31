<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Http\Session;
use Johncms\Modules\Notifications\Application\UseCases\ClearNotificationsUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class ClearController
{
    public function __construct(
        private Session $session,
        private ClearNotificationsUseCase $clearNotificationsUseCase,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        return $this->clear('/notifications/');
    }

    public function admin(): RedirectResponse
    {
        return $this->clear('/admin/notifications');
    }

    private function clear(string $returnUrl): RedirectResponse
    {
        $this->clearNotificationsUseCase->execute();
        $this->session->flash('message', d__('notifications', 'Notifications are cleared!'));
        return new RedirectResponse($returnUrl);
    }
}
