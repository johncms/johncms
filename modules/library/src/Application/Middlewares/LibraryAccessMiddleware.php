<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Middlewares;

use Johncms\Router\MiddlewareInterface;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class LibraryAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $currentUser,
        private Render $render,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        $config = config('johncms');
        $error = '';

        if (! $config['mod_lib'] && $this->currentUser->rights < 7) {
            $error = __('Library is closed');
        } elseif ($config['mod_lib'] === 1 && ! $this->currentUser->isValid()) {
            $error = __('Access forbidden');
        }

        if ($error) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'   => __('Library'),
                'type'    => 'alert-danger',
                'message' => $error,
            ]);
        }

        return $next($request);
    }
}
