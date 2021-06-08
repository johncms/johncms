<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Guestbook\Resources;

abstract class BaseResource
{
    /** @var mixed */
    protected $model;

    /**
     * BaseResource constructor.
     *
     * @param mixed $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    abstract public function toArray(): array;
}
