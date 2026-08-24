<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Manifest;

/**
 * What a module needs before it can be loaded: a version of PHP, a version of the CMS, and other
 * modules it is built against.
 *
 * Constraints are written the way Composer writes them (`^10.0`, `>=8.4`), and are read by the
 * same library — a module author already knows that language, and the CMS has no business
 * inventing a second one.
 *
 * A module of the release usually declares nothing: it ships with the CMS, so its version of PHP
 * and of the CMS are the ones it was released with. What it does declare is the modules it is
 * built against, because those can be switched off.
 */
final readonly class ModuleRequirements
{
    /**
     * @param array<string, string> $modules Module key to a version constraint.
     */
    public function __construct(
        public ?string $php = null,
        public ?string $johncms = null,
        public array $modules = [],
    ) {
    }
}
