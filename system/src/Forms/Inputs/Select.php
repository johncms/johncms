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

class Select extends AbstractInput
{
    public string $type = 'select';

    public array $options = [];

    public bool $multiple = false;

    public function setOptions(array $options): Select
    {
        $this->options = $options;
        return $this;
    }

    public function multiple(): Select
    {
        $this->multiple = true;
        return $this;
    }
}
