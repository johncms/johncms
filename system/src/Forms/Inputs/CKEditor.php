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

class CKEditor extends AbstractInput
{
    public string $type = 'ckeditor';
    public string $uploadUrl = '';
    public string $filesInputName = 'attached_files[]';

    public function setUploadUrl(string $uploadUrl): static
    {
        $this->uploadUrl = $uploadUrl;
        return $this;
    }

    public function setFilesInputName(string $filesInputName): static
    {
        $this->filesInputName = $filesInputName;
        return $this;
    }
}
