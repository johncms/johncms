<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Smilies\SmiliesCacheWriteException;
use Johncms\Smilies\SmiliesMap;

final readonly class RebuildSmiliesCacheUseCase
{
    public function __construct(private SmiliesMap $smilies)
    {
    }

    /**
     * Builds the map again if the folders changed since it was built, and returns how many
     * pictures it holds then; null when it is up to date.
     *
     * @throws SmiliesCacheWriteException
     */
    public function refreshIfStale(): ?int
    {
        return $this->smilies->isStale() ? $this->execute() : null;
    }

    /**
     * Builds the map of the smilies again and returns how many pictures it holds: a picture has
     * a Latin and a Cyrillic code, so the codes would count it twice.
     *
     * @throws SmiliesCacheWriteException
     */
    public function execute(): int
    {
        $map = $this->smilies->rebuild();

        return count(array_unique(array_merge(array_values($map['usr']), array_values($map['adm']))));
    }
}
