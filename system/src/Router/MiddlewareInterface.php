<?php

declare(strict_types=1);

namespace Johncms\Router;

use Johncms\System\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed;
}
