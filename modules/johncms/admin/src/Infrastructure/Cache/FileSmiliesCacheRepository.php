<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Cache;

use Johncms\Modules\Admin\Domain\Exceptions\SmiliesCacheWriteException;
use Johncms\Modules\Admin\Domain\Repository\SmiliesCacheRepositoryInterface;

final class FileSmiliesCacheRepository implements SmiliesCacheRepositoryInterface
{
    private const CACHE_FILE = 'smilies-list.cache';

    public function save(array $smilies): void
    {
        if (file_put_contents(CACHE_PATH . self::CACHE_FILE, serialize($smilies)) === false) {
            throw new SmiliesCacheWriteException('Can not write smilies cache file');
        }
    }
}
