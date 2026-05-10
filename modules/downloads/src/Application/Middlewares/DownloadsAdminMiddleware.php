<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Middlewares;

use Johncms\Router\MiddlewareInterface;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class DownloadsAdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $currentUser,
        private Render $render,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        if ($this->currentUser->rights < 6 && $this->currentUser->rights !== 4) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('Downloads'),
                'type'          => 'alert-danger',
                'message'       => __('Not found'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ]);
        }

        return $next($request);
    }
}
