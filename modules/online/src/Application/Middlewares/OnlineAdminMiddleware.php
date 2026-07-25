<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Middlewares;

use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class OnlineAdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $currentUser,
        private Render $render,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        if (! $this->currentUser->rights) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'   => __('Online'),
                'type'    => 'alert-danger',
                'message' => __('Access denied'),
            ]);
        }

        return $next($request);
    }
}
