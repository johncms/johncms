<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\UseCases;

use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\Users\User;
use Johncms\Utils\Transliterator;

final readonly class ViewSearchUseCase
{
    public function __construct(
        private CommunityUserRepositoryInterface $communityUserRepository,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function validate(string $search): array
    {
        $errors = [];
        if ($search !== '' && (mb_strlen($search) < 2 || mb_strlen($search) > 20)) {
            $errors[] = __('Nickname') . ': ' . __('Invalid length');
        }

        return $errors;
    }

    public function count(string $search): int
    {
        return $this->communityUserRepository->countUsersByLatinNameLike($this->buildSearchLike($search));
    }

    /**
     * @return array<int, User>
     */
    public function getPage(string $search, int $limit, int $offset): array
    {
        return $this->communityUserRepository
            ->getUsersByLatinNameLike($limit, $offset, $this->buildSearchLike($search))
            ->all();
    }

    private function buildSearchLike(string $search): string
    {
        return '%' . Transliterator::toLatin($search) . '%';
    }
}
