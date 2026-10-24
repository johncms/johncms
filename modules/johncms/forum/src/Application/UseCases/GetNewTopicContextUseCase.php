<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Access\ForumAccess;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

final readonly class GetNewTopicContextUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private ForumAccess $forumAccess,
    ) {
    }

    public function execute(int $sectionId): ForumSection
    {
        if (
            ! $this->currentUser->isValid()
            || $this->forumAccess->isBanned()
            || ! $this->accessChecker->allows(ForumPermissions::POST)
        ) {
            throw new ForumAccessDeniedException('Access denied to create topic.');
        }

        $section = ForumSection::query()
            ->where('section_type', 1)
            ->where('id', $sectionId)
            ->first();

        if ($section === null) {
            throw new ForumNotFoundException('Section not found.');
        }

        return $section;
    }
}
