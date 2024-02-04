<?php

declare(strict_types=1);

namespace Johncms\Content\Resources;

use Johncms\Content\Models\ContentElement;
use Johncms\Http\Resources\AbstractResource;

/**
 * @mixin ContentElement
 */
class ContentElementResource extends AbstractResource
{
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'code'      => $this->code,
            'deleteUrl' => route('content.admin.elements.delete', ['id' => $this->id]),
            'url'       => route('content.admin.elements.edit', ['elementId' => $this->id]),
        ];
    }
}
