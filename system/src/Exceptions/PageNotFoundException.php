<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Exceptions;

use RuntimeException;

class PageNotFoundException extends RuntimeException
{
    /** @var string */
    protected $title = 'ERROR: 404 Not Found';

    /** @var string */
    protected $template = 'system::error/404';

    public function setTitle(string $title): PageNotFoundException
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTemplate(string $template): PageNotFoundException
    {
        $this->template = $template;
        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
