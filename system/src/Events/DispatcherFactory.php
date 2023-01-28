<?php

declare(strict_types=1);

namespace Johncms\Events;

use Illuminate\Events\Dispatcher;
use Psr\Container\ContainerInterface;

class DispatcherFactory
{
    public function __invoke(ContainerInterface $container): Dispatcher
    {
        return $container->get(Dispatcher::class);
    }
}
