<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\PollVotersQueryDTO;
use Johncms\Modules\Forum\Application\DTO\PollVotersResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;

final readonly class ViewPollVotersUseCase
{
    public function __construct(
        private StaffTitles $staffTitles,
        private ForumVoteRepositoryInterface $voteRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(PollVotersQueryDTO $query): PollVotersResultDTO
    {
        $poll = $this->voteRepository->findPollByTopic($query->topicId);
        if ($poll === null) {
            throw new ForumValidationException('Poll not found.');
        }

        $limit = (int) $this->currentUser->user()->config->kmess;
        $start = (max(1, $query->page) - 1) * max(1, $limit);
        $total = $this->voteRepository->countUsersByTopic($query->topicId);
        $items = [];

        if ($total > 0) {
            $items = $this->mapItems(
                $this->voteRepository->getUsersByTopic(
                    topicId: $query->topicId,
                    start: $start,
                    limit: $limit,
                )
            );
        }

        return new PollVotersResultDTO(
            pollName: $poll->name,
            items: $items,
            total: $total,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapItems(array $rows): array
    {
        $items = [];
        $currentTime = time();
        $this->staffTitles->preload(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows));

        foreach ($rows as $row) {
            $row['user_profile_link'] = '';
            if (! empty($row['id']) && $this->currentUser->isValid() && $this->currentUser->id() !== (int) $row['id']) {
                $row['user_profile_link'] = '/profile/' . $row['id'];
            }

            $row['user_rights_name'] = $this->staffTitles->titleFor((int) ($row['id'] ?? 0));
            $row['user_is_online'] = $currentTime <= (int) ($row['lastdate'] ?? 0) + 300;

            $ip = long2ip((int) ($row['ip'] ?? 0));
            $row['search_ip_url'] = '/admin/ip-search?ip=' . $ip;
            $row['ip'] = $ip;

            $proxyIp = (int) ($row['ip_via_proxy'] ?? 0);
            $row['search_ip_via_proxy_url'] = '/admin/ip-search?ip=' . long2ip($proxyIp);
            $row['ip_via_proxy'] = $proxyIp > 0 ? long2ip($proxyIp) : 0;

            $row['place'] = '';
            $items[] = $row;
        }

        return $items;
    }
}
