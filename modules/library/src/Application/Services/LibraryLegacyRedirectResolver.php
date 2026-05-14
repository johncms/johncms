<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

final class LibraryLegacyRedirectResolver
{
    public function resolve(array $query): ?string
    {
        if (($query['act'] ?? '') !== 'del') {
            return null;
        }

        $type = isset($query['type']) ? trim((string) $query['type']) : '';
        $id = isset($query['id']) ? max(0, (int) $query['id']) : 0;

        if ($id <= 0) {
            return null;
        }

        return match ($type) {
            'article' => '/library/article/' . $id . '/delete',
            'dir'     => '/library/section/' . $id . '/delete',
            'image'   => '/library/article/' . $id . '/image/delete',
            default   => null,
        };
    }
}
