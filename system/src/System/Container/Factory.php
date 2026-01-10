<?php

/**
 * This file is part of mobiCMS Content Management System.
 *
 * @copyright   Oleg Kasyanov <dev@mobicms.net>
 * @license     https://opensource.org/licenses/GPL-3.0 GPL-3.0 (see the LICENSE.md file)
 * @link        http://mobicms.org mobiCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Container;

use Johncms\Container\PSRContainerFactory;
use Psr\Container\ContainerInterface;

class Factory
{
    public static function getContainer(): ContainerInterface
    {
        return PSRContainerFactory::getContainer();
    }
}
