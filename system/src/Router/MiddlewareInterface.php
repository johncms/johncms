<?php

declare(strict_types=1);

namespace Johncms\Router;

use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
