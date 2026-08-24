<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Middlewares;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\AdminAreaContext;
use Johncms\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\View\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The gate of everything that changes the site as a whole: system settings, IP bans, karma,
 * counters, languages, maintenance, deleting an account.
 *
 * Self-contained: the middleware of the surrounding admin group does not reach into a nested
 * group, so a guest and a visitor without the permission are answered here as well.
 */
final readonly class SuperAdminAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private RendererInterface $renderer,
        private Translator $translator,
        private AdminAreaContext $adminArea,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->currentUser->isGuest()) {
            redirect('/admin/login');
        }

        if (! $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE)) {
            return $this->renderForbidden();
        }

        $this->adminArea->enter();

        return $next($request);
    }

    private function renderForbidden(): Response
    {
        $this->translator->addTranslationDomain('admin', MODULES_PATH . 'johncms/admin/locale', false);

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
