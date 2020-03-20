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

use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use PDO;
use Psr\Container\ContainerInterface;

class CountersFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new Counters(
            $container->get(PDO::class),
            $container->get(Tools::class),
            $container->get(User::class),
            $container->get('config')['johncms']['homeurl']
        );
    }
}
