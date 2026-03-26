<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\UseCases;

use Johncms\Modules\Community\Application\DTO\CommunitySearchResultDTO;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\System\Legacy\Tools;

final readonly class ViewSearchUseCase
{
    public function __construct(
        private CommunityUserRepositoryInterface $communityUserRepository,
        private Tools $tools,
    ) {
    }

    public function execute(string $search, int $perPage): CommunitySearchResultDTO
    {
        $search = trim($search);
        $title = __('User Search');
        $errors = [];
        $total = 0;
        $list = [];
        $pagination = '';

        if ($search !== '' && (mb_strlen($search) < 2 || mb_strlen($search) > 20)) {
            $errors[] = __('Nickname') . ': ' . __('Invalid length');
        }

        if ($search !== '' && $errors === []) {
            $searchDb = '%' . $this->tools->rusLat($search) . '%';
            $users = $this->communityUserRepository->paginateUsersByLatinNameLike($perPage, $searchDb);
            $total = $users->total();
            $list = $users->items();
            $pagination = $users->render();
        }

        return new CommunitySearchResultDTO(
            $title,
            $title,
            $search,
            $errors,
            $total,
            $list,
            $pagination,
        );
    }
}
