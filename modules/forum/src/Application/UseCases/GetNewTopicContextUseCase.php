<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Users\User;

final readonly class GetNewTopicContextUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private User $currentUser,
    ) {
    }

    public function execute(int $sectionId): ForumSection
    {
        if (
            ! $this->currentUser->is_valid
            || isset($this->currentUser->ban['1'])
            || isset($this->currentUser->ban['11'])
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
