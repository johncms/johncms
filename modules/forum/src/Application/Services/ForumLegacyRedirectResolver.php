<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

final class ForumLegacyRedirectResolver
{
    public function resolve(array $query): ?string
    {
        $act = isset($query['act']) ? trim((string) $query['act']) : '';

        if ($act === 'file') {
            $id = isset($query['id']) ? abs((int) $query['id']) : 0;

            if ($id > 0) {
                return '/forum/download-file/' . $id . '/';
            }

            return '/forum/';
        }

        return null;
    }
}
