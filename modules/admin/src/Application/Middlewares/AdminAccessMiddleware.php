<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Middlewares;

use Johncms\Modules\Admin\Domain\Enums\UserRights;
use Johncms\Router\MiddlewareInterface;
use Johncms\System\Http\Request;
use Johncms\Users\User;

/**
 * Базовый гейт админпанели: доступ только авторизованным пользователям
 * с правами не ниже администратора. Гость отправляется на экран входа.
 * Действия с повышенными требованиями (rights >= 9) проверяются дополнительно
 * в соответствующих Ensure*AccessUseCase.
 */
final readonly class AdminAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        if (! $this->user->isValid()) {
            redirect('/admin/login');
        }

        if ($this->user->rights < UserRights::ADMIN->value) {
            pageNotFound();
        }

        return $next($request);
    }
}
