<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\PollVotersQueryDTO;
use Johncms\Modules\Forum\Application\DTO\PollVotersResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class ViewPollVotersUseCase
{
    public function __construct(
        private ForumVoteRepositoryInterface $voteRepository,
        private User $currentUser,
    ) {
    }

    public function execute(PollVotersQueryDTO $query): PollVotersResultDTO
    {
        $poll = $this->voteRepository->findPollByTopic($query->topicId);
        if ($poll === null) {
            throw new ForumValidationException('Poll not found.');
        }

        $limit = (int) $this->currentUser->config->kmess;
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
        $userRightsNames = $this->getUserRightsNames();
        $currentTime = time();

        foreach ($rows as $row) {
            $row['user_profile_link'] = '';
            if (! empty($row['id']) && $this->currentUser->isValid() && $this->currentUser->id !== (int) $row['id']) {
                $row['user_profile_link'] = '/profile/' . $row['id'];
            }

            $row['user_rights_name'] = $userRightsNames[(int) ($row['rights'] ?? 0)] ?? '';
            $row['user_is_online'] = $currentTime <= (int) ($row['lastdate'] ?? 0) + 300;

            $ip = long2ip((int) ($row['ip'] ?? 0));
            $row['search_ip_url'] = '/admin/search_ip/?ip=' . $ip;
            $row['ip'] = $ip;

            $proxyIp = (int) ($row['ip_via_proxy'] ?? 0);
            $row['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . long2ip($proxyIp);
            $row['ip_via_proxy'] = $proxyIp > 0 ? long2ip($proxyIp) : 0;

            $row['place'] = '';
            $items[] = $row;
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function getUserRightsNames(): array
    {
        return [
            3 => __('Forum moderator'),
            4 => __('Download moderator'),
            5 => __('Library moderator'),
            6 => __('Super moderator'),
            7 => __('Administrator'),
            9 => __('Supervisor'),
        ];
    }
}
