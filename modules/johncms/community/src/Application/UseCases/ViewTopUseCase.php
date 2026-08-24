<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\UseCases;

use Johncms\Modules\Community\Application\DTO\CommunityTopResultDTO;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;

final readonly class ViewTopUseCase
{
    public function __construct(
        private CommunityUserRepositoryInterface $communityUserRepository,
    ) {
    }

    public function execute(string $mod): CommunityTopResultDTO
    {
        $url = '/community/top/';
        $tabs = [
            'forum' => [
                'name'   => __('Forum'),
                'url'    => $url,
                'active' => false,
            ],
            'guest' => [
                'name'   => __('Guestbook'),
                'url'    => $url . 'guest/',
                'active' => false,
            ],
            'comm'  => [
                'name'   => __('Comments'),
                'url'    => $url . 'comm/',
                'active' => false,
            ],
        ];

        $config = config('johncms');
        if ($config['karma']) {
            $tabs['karma'] = [
                'name'   => __('Karma'),
                'url'    => $url . 'karma/',
                'active' => false,
            ];
        }

        switch ($mod) {
            case 'guest':
                $users = $this->communityUserRepository->getTopGuestbookUsers(9);
                $title = __('Most active in Guestbook');
                $active = 'guest';
                break;

            case 'comm':
                $users = $this->communityUserRepository->getTopCommentUsers(9);
                $title = __('Most commentators');
                $active = 'comm';
                break;

            case 'karma':
                if ($config['karma']) {
                    $users = $this->communityUserRepository->getTopKarmaUsers(9);
                    $title = __('Best Karma');
                    $active = 'karma';
                    break;
                }
                $users = $this->communityUserRepository->getTopForumUsers(9);
                $title = __('Most active in Forum');
                $active = 'forum';
                break;

            default:
                $users = $this->communityUserRepository->getTopForumUsers(9);
                $title = __('Most active in Forum');
                $active = 'forum';
        }

        $tabs[$active]['active'] = true;

        return new CommunityTopResultDTO(
            $title,
            $title,
            $active,
            $tabs,
            $users->all(),
            $users->count(),
        );
    }
}
