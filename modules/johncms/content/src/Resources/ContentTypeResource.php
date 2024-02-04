<?php

declare(strict_types=1);

namespace Johncms\Content\Resources;

use Johncms\Content\Models\ContentType;
use Johncms\Http\Resources\AbstractResource;

/**
 * @mixin ContentType
 */
class ContentTypeResource extends AbstractResource
{
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'code'      => $this->code,
            'url'       => route('content.admin.sections', ['type' => $this->id]),
            'editUrl'   => route('content.admin.type.edit', ['id' => $this->id]),
            'deleteUrl' => route('content.admin.type.delete', ['id' => $this->id]),
        ];
    }
}
