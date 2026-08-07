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
    /**
     * Empty on purpose: a property default has to be a constant expression, so the translated
     * default cannot live here. The HTTP layer applies it (see Http\ExceptionResponseFactory).
     */
    protected string $title = '';

    protected string $template = '@theme/pages/errors/404.twig';

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
