<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Middlewares;

use Johncms\Modules\Admin\Domain\Enums\UserRights;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\View\RendererInterface;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Базовый гейт админпанели: доступ только авторизованным пользователям
 * с правами не ниже администратора. Гость отправляется на экран входа,
 * авторизованный пользователь без достаточных прав получает 403 без админ-обвеса.
 * Действия с повышенными требованиями (rights >= 9) проверяются дополнительно
 * в соответствующих Ensure*AccessUseCase.
 */
final readonly class AdminAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $user,
        private RendererInterface $renderer,
        private Translator $translator,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->user->isValid()) {
            redirect('/admin/login');
        }

        if ($this->user->rights < UserRights::ADMIN->value) {
            return $this->renderForbidden();
        }

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
