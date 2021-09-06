<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Forms\Inputs;

class Checkbox extends AbstractInput
{
    public string $type = 'checkbox';
    public bool $checked = false;

    public function setChecked(bool $checked): static
    {
        $this->checked = $checked;
        return $this;
    }
}
