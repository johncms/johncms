<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Modules\Admin\Domain\Exceptions\SmiliesCacheWriteException;

interface SmiliesCacheRepositoryInterface
{
    /**
     * @param array{usr: array<string, string>, adm: array<string, string>} $smilies
     *
     * @throws SmiliesCacheWriteException
     */
    public function save(array $smilies): void;
}
