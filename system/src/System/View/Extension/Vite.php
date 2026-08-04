<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\View\Extension;

use Johncms\View\Asset\Vite as ViteTags;
use Mobicms\Render\Engine;
use Mobicms\Render\ExtensionInterface;

/**
 * Publishes the Vite tags to Plates templates. The tags themselves are built by the shared
 * service, so both engines emit the same markup for the same entry point.
 */
final readonly class Vite implements ExtensionInterface
{
    public function __construct(private ViteTags $vite)
    {
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('vite', $this->vite->tags(...));
    }
}
