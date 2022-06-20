<?php

namespace Johncms\Admin\Controllers\System;

use DebugBar\DebugBarException;
use DebugBar\OpenHandler;
use Johncms\Debug\DebugBar;
use Johncms\Users\User;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class DebugBarController
{
    /**
     * @throws DebugBarException
     * @throws Throwable
     */
    public function index(?User $user): string | ResponseInterface
    {
        $debug = (DEBUG_FOR_ALL || (DEBUG && $user?->isAdmin()));
        if ($debug) {
            error_reporting(E_ALL ^ E_DEPRECATED);
            $debugBar = di(DebugBar::class);
            $openHandler = new OpenHandler($debugBar);
            return $openHandler->handle(echo: false);
        }
        return status_page(403);
    }
}
