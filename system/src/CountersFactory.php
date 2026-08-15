<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Users\User;
use PDO;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CountersFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new Counters(
            $container->get(PDO::class),
            $container->get(User::class),
            config('johncms.homeurl', ''),
            $container->get(Cache::class),
            $container->get(RequestStack::class),
            $container->get(AccessCheckerInterface::class)
        );
    }
}
