<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Notifications\Application\UseCases\ClearNotificationsUseCase;

final readonly class ClearController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private ClearNotificationsUseCase $clearNotificationsUseCase,
    ) {
        $this->controllerContext->initModule('notifications');
    }

    public function __invoke(): never
    {
        $this->clearNotificationsUseCase->execute();
        $_SESSION['message'] = __('Notifications are cleared!');
        header('Location: /notifications/');
        exit;
    }
}
