<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumFile;

interface ForumFileRepositoryInterface
{
    public function save(ForumFile $file): void;
}
