<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Homepage;

use Johncms\Modules\Online\Domain\Repository\OnlineGuestRepositoryInterface;
use Johncms\Modules\Online\Domain\Repository\OnlineUserRepositoryInterface;
use Johncms\Users\ProfileLinksInterface;
use Johncms\View\Homepage\HomepageBlock;
use Johncms\View\Homepage\HomepageBlockPosition;
use Johncms\View\Homepage\HomepageBlockProviderInterface;

final readonly class OnlineBlockProvider implements HomepageBlockProviderInterface
{
    public function __construct(
        private OnlineUserRepositoryInterface $users,
        private OnlineGuestRepositoryInterface $guests,
        private ProfileLinksInterface $profileLinks,
    ) {
    }

    public function homepageBlocks(): iterable
    {
        $items = [];
        foreach ($this->users->getOnline(8, 0) as $user) {
            $items[] = [
                'id' => $user->id,
                'name' => $user->name,
                'url' => $this->profileLinks->urlOf($user->id),
            ];
        }

        yield new HomepageBlock('@online/homepage/users.twig', [
            'users' => $items,
            'user_count' => $this->users->countOnline(),
            'guest_count' => $this->guests->countOnline(),
        ], position: HomepageBlockPosition::Bottom);
    }
}
