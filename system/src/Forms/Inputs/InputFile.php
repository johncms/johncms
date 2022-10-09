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

class InputFile extends AbstractInput
{
    public string $type = 'file';

    /** @var array{id: int, name: string, url: string, isImage: bool, delInputName: string} */
    public array $currentFile = [];

    public function setCurrentFile(array $file): static
    {
        $this->currentFile = $file;
        return $this;
    }
}
