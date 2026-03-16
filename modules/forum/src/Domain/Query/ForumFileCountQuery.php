<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Query;

final readonly class ForumFileCountQuery
{
    private function __construct(
        public ForumFileScopeQuery $scope,
        public bool $isNew,
        public int $fileType,
        public int $newFrom,
    ) {
    }

    public static function forNew(ForumFileScopeQuery $scope, int $newFrom): self
    {
        return new self(
            scope: $scope,
            isNew: true,
            fileType: 0,
            newFrom: $newFrom,
        );
    }

    public static function forType(ForumFileScopeQuery $scope, int $fileType): self
    {
        return new self(
            scope: $scope,
            isNew: false,
            fileType: $fileType,
            newFrom: 0,
        );
    }
}
