<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Middlewares;

use Johncms\Modules\Admin\Domain\Enums\UserRights;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\AdminAreaContext;
use Johncms\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\View\RendererInterface;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

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
        private RendererInterface $renderer,
        private Translator $translator,
        private AdminAreaContext $adminArea,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->user->isValid()) {
            redirect('/admin/login');
        }

        if ($this->user->rights < UserRights::SUPER_ADMIN->value) {
            return $this->renderForbidden();
        }

        $this->adminArea->enter();

        return $next($request);
    }

    private function renderForbidden(): Response
    {
        $this->translator->addTranslationDomain('admin', MODULES_PATH . 'admin/locale', false);

        return new Response(
            $this->renderer->render(
                '@admin/pages/errors/403.twig',
                [
                    'title'   => d__('admin', 'Access denied'),
                    'message' => '',
                ]
            ),
            Response::HTTP_FORBIDDEN
        );
    }
}
