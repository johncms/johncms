<?php

declare(strict_types=1);

namespace Johncms\Http;

final readonly class PageMeta
{
    public string $title;
    public string $description;

    public function __construct(
        string $title,
        int $page,
        string $description = '',
    ) {
        $suffix = $page > 1
            ? ' — ' . d__('system', 'Page') . ' ' . $page
            : '';

        $this->title       = $title . $suffix;
        $effective         = $description !== '' ? $description : $title;
        $this->description = $effective . $suffix;
    }
}
