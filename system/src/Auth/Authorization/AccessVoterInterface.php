<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use Johncms\Auth\Identity;

/**
 * One rule in the answer to "may this identity do this".
 *
 * Registered with the `johncms.auth.voter` tag, which is how a module adds a rule of its own —
 * a moderator of one forum section, the author of a post editing it — without the core knowing
 * about it. The optional subject is what such a rule needs: the section, the post, the profile.
 */
interface AccessVoterInterface
{
    /**
     * Whether this voter has anything to say about the pair. Keep it cheap: it runs for every
     * check, while vote() only runs when this returns true.
     */
    public function supports(string $permission, mixed $subject): bool;

    public function vote(Identity $identity, string $permission, mixed $subject): Vote;
}
