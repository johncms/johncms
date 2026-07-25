<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Middlewares;

use Johncms\Modules\Admin\Domain\Enums\UserRights;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
use Johncms\Users\User;

/**
 * Гейт действий с повышенными требованиями (rights >= 9): системные настройки,
 * управление IP-банами, кармой, счётчиками, языками, удаление пользователей и т.п.
 * Самодостаточен: middleware родительской admin-группы во вложенные группы не
 * пробрасывается, поэтому гость и недостаточные права обрабатываются здесь же.
 */
final readonly class SuperAdminAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $user,
        private Render $render,
        private Translator $translator,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        if (! $this->user->isValid()) {
            redirect('/admin/login');
        }

        if ($this->user->rights < UserRights::SUPER_ADMIN->value) {
            $this->renderForbidden();
        }

        return $next($request);
    }

    private function renderForbidden(): never
    {
        $this->translator->addTranslationDomain('admin', MODULES_PATH . 'admin/locale', false);

        if (! headers_sent()) {
            header('HTTP/1.0 403 Forbidden');
        }

        echo $this->render->render(
            'system::error/403',
            [
                'title'   => d__('admin', 'Access denied'),
                'message' => '',
            ]
        );
        exit;
    }
}
