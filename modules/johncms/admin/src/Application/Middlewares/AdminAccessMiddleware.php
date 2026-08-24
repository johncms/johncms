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
 * The gate of the admin panel: only for those allowed to open it at all. A guest is sent to the
 * sign-in screen, somebody signed in without the permission gets a 403 without the trimmings of
 * the panel around it.
 *
 * Screens that ask for more than opening the panel check their own permission on top of this one.
 */
final readonly class AdminAccessMiddleware implements MiddlewareInterface
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

        if (! $this->accessChecker->allows(CorePermissions::ADMIN_ACCESS)) {
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
