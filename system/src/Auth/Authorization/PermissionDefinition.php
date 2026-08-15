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

/**
 * One permission a module declares: the key checks are written against, plus what to call it
 * in the role editor.
 *
 * The catalogue lives in code rather than in the database so that switching a module off takes
 * its permissions out of the editor without touching what roles were granted.
 */
final readonly class PermissionDefinition
{
    /**
     * @param string      $key        Dot-separated, `<module>.<resource>.<action>`: `forum.topic.delete`.
     * @param string      $group      The module the permission belongs to; groups the role editor.
     * @param string      $label      Human-readable and already translated — modules pass d__('forum', …).
     * @param string|null $groupLabel What to call the group above the checkboxes, translated. Null
     *                                falls back to the group key, so a module that never spells the
     *                                title out is still listed under a heading of its own.
     */
    public function __construct(
        public string $key,
        public string $group,
        public string $label,
        public ?string $groupLabel = null,
    ) {
    }
}
