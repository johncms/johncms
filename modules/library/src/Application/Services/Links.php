<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Johncms\System\Legacy\Tools;

class Links
{
    private string $link_url;

    private array $in;

    private array $links = [];

    private string $res = '';

    private Tools $tools;

    public function __construct(array $in, string $link_url = '/library/tags?tag=')
    {
        $this->link_url = $link_url;
        $this->in       = $in;
        $this->tools    = di(Tools::class);
    }

    public function proccess(string $tpl): self|false
    {
        if ($this->in) {
            $this->links = array_map([$this, $tpl], $this->in);
            $this->res   = implode('', $this->links);

            return $this;
        }

        return false;
    }

    public function tplTag(string $n): string
    {
        return '<a href="' . $this->link_url . $n . '">' . $this->tools->checkout($n) . '</a>';
    }

    public function tplCloud(array $n): string
    {
        return '<a href="' . $this->link_url . $this->tools->checkout($n['name']) . '"><span style="font-size: ' . $n['rang'] . ' em;">' . $this->tools->checkout($n['name']) . '</span></a>';
    }

    public function linkSeparator(string $separator = ' | '): self|false
    {
        if ($this->in) {
            $this->res = implode($separator, $this->links !== [] ? $this->links : $this->in);

            return $this;
        }

        return false;
    }

    public function result(): string
    {
        return $this->res;
    }

    public function getIn(): array
    {
        return $this->in;
    }
}
