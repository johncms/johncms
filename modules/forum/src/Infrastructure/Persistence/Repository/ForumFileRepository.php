<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;

final class ForumFileRepository implements ForumFileRepositoryInterface
{
    public function save(ForumFile $file): void
    {
        $file->save();
    }
}
