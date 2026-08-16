<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

use Psr\Container\ContainerInterface;

/**
 * Class HTMLPurifier
 *
 * @package Johncms\Security
 * @mixin \HTMLPurifier
 *
 * @deprecated Take HtmlSanitizerInterface instead. The callers that still ask the container for
 *             a raw \HTMLPurifier are being moved over to it; this factory goes away with the
 *             last of them.
 */
class HTMLPurifier
{
    public function __invoke(ContainerInterface $container): \HTMLPurifier
    {
        return (new HtmlPurifierFactory())->create(HtmlPolicy::RichContent);
    }

    public static function create(ContainerInterface $container)
    {
        return (new self())($container);
    }
}
