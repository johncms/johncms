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

/**
 * An extension point: a module that has a kind of content none of the built-in policies fits
 * declares its own here, by registering a service that implements this interface. Nothing in
 * the core has to be edited for it, so the policy survives an update of the CMS.
 */
interface HtmlPolicyProviderInterface
{
    /**
     * @return iterable<HtmlPolicyDefinition>
     */
    public function policies(): iterable;
}
