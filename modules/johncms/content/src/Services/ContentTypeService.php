<?php

declare(strict_types=1);

namespace Johncms\Content\Services;

use Illuminate\Support\Facades\DB;
use Johncms\Content\Models\ContentElement;
use Johncms\Content\Models\ContentSection;
use Johncms\Content\Models\ContentType;

class ContentTypeService
{
    public function delete(ContentType $contentType): void
    {
        DB::transaction(function () use ($contentType) {
            ContentSection::query()->where('content_type_id', $contentType->id)->delete();
            ContentElement::query()->where('content_type_id', $contentType->id)->delete();
            $contentType->delete();
        });
    }
}
