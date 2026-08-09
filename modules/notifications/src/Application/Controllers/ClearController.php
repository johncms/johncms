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
        $this->clearNotificationsUseCase->execute();
        $this->session->flash('message', __('Notifications are cleared!'));
        return new RedirectResponse('/notifications/');
    }
}
