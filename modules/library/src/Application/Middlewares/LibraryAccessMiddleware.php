<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Middlewares;

use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class LibraryAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $currentUser,
        private Render $render,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $config = config('johncms');
        $error = '';

        if (! $config['mod_lib'] && $this->currentUser->rights < 7) {
            $error = __('Library is closed');
        } elseif ($config['mod_lib'] === 1 && ! $this->currentUser->isValid()) {
            $error = __('Access forbidden');
        }

        if ($error) {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'   => __('Library'),
                    'type'    => 'alert-danger',
                    'message' => $error,
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
