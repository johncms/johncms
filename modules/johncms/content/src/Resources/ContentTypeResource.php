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
            'id'   => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'url'  => '/', // TODO: Change url
        ];
    }
}
