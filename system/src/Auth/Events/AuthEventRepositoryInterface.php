<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Events;

interface AuthEventRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function store(array $attributes): void;

    /**
     * @return int Number of rows removed.
     */
    public function deleteBefore(int $timestamp): int;
}
