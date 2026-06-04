<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Profile\Application\DTO\ProfileViewDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\KarmaRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetProfileViewUseCase
{
    private const APPENDS = [
        'is_online',
        'rights_name',
        'profile_url',
        'search_ip_url',
        'whois_ip_url',
        'search_ip_via_proxy_url',
        'whois_ip_via_proxy_url',
        'is_birthday',
        'birthday_date',
        'display_place',
        'formatted_about',
        'website',
        'photo',
        'last_visit',
    ];

    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private ContactRepositoryInterface $contactRepository,
        private KarmaRepositoryInterface $karmaRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId): ProfileViewDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($profileUser === null || (! $profileUser->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        $config = config('johncms');
        $isOwner = $profileUser->id === $this->currentUser->id;
        $contactState = $this->contactState($profileUser->id);

        $userData = $profileUser->setAppends(self::APPENDS)->toArray();

        // IP history
        $userData['ip_history_count'] = $profileUser->ipHistory()->count();
        $userData['ip_history_url'] = '/profile/' . $profileUser->id . '/ip-history';

        // Karma
        if ($config['karma']['on']) {
            $userData += $this->buildKarmaData($profileUser, $config);
        }

        $notifications = $this->buildNotifications($profileUser, $config);

        $activeBan = false;
        $activeBanReason = '';
        $lastBan = $profileUser->bans()->orderBy('ban_time', 'desc')->first();
        if ($lastBan !== null) {
            $activeBan = $lastBan->is_active;
            $activeBanReason = $lastBan->ban_reason;
        }

        return new ProfileViewDTO(
            title: $isOwner ? __('My Profile') : __('User Profile'),
            user: $userData,
            showIp: $this->currentUser->rights > 0 && $this->currentUser->rights >= $profileUser->rights,
            canWrite: ! $this->isIgnored($profileUser->id)
                && $contactState !== 2
                && ! isset($this->currentUser->ban['1'])
                && ! isset($this->currentUser->ban['3']),
            blocked: $contactState === 2,
            notifications: $notifications,
            activeBan: $activeBan,
            activeBanReason: $activeBanReason,
            counters: ['ban' => $profileUser->bans()->count()],
            buttons: $this->buildButtons($profileUser, $contactState),
        );
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function buildKarmaData(User $profileUser, array $config): array
    {
        $data = [];
        $karmaPoints = $profileUser->karma_plus - $profileUser->karma_minus;
        $data['karma_points'] = $karmaPoints;

        if (! empty($profileUser->karma_plus) || ! empty($profileUser->karma_minus)) {
            $data['karma_percent'] = round($karmaPoints / (($profileUser->karma_plus + $profileUser->karma_minus) / 100));
        } else {
            $data['karma_percent'] = 0;
        }

        $data['positive_url'] = '/profile/?act=karma&user=' . $profileUser->id . '&type=1';
        $data['negative_url'] = '/profile/?act=karma&user=' . $profileUser->id;

        if ($profileUser->id !== $this->currentUser->id) {
            $canVote = ! $this->currentUser->karma_off
                && (! $profileUser->rights || ($profileUser->rights && ! $config['karma']['adm']))
                && $profileUser->ip !== $this->currentUser->ip;
            if ($canVote) {
                $sum = $this->karmaRepository->sumPointsGivenSince($this->currentUser->id, $this->currentUser->karma_time);
                $count = $this->karmaRepository->countVotesGivenTo(
                    $this->currentUser->id,
                    $profileUser->id,
                    $this->currentUser->karma_time,
                    time() - 86400
                );
                if (
                    empty($this->currentUser->ban)
                    && $this->currentUser->postforum >= $config['karma']['forum']
                    && ($config['karma']['karma_points'] - $sum) > 0
                    && ! $count
                ) {
                    $data['vote_url'] = '/profile/?act=karma&mod=vote&user=' . $profileUser->id;
                }
            }
        } else {
            $totalKarma = $this->karmaRepository->countVotesReceivedAfter($this->currentUser->id, time() - 86400);
            if ($totalKarma > 0) {
                $data['karma_new_url'] = '/profile/?act=karma&mod=new';
                $data['karma_new'] = $totalKarma;
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $config
     * @return string[]
     */
    private function buildNotifications(User $profileUser, array $config): array
    {
        $notifications = [];
        if ($this->currentUser->rights >= 7 && ! $profileUser->preg && empty($profileUser->regadm)) {
            $notifications[] = __('Pending confirmation');
        }
        if (! empty($config['user_email_confirmation']) && ! $profileUser->email_confirmed) {
            $notifications[] = __('E-mail address is not confirmed');
        }

        return $notifications;
    }

    /**
     * @return array<int, array{url: string, name: string}>
     */
    private function buildButtons(User $profileUser, int $contactState): array
    {
        $buttons = [];
        $isOwner = $profileUser->id === $this->currentUser->id;

        if ($contactState !== 2) {
            $buttons[] = $contactState === 0
                ? ['url' => '/mail/add/' . $profileUser->id, 'name' => __('Add to Contacts')]
                : ['url' => '/mail/delete-contact/' . $profileUser->id, 'name' => __('Remove from Contacts')];
        }

        if (
            $isOwner
            || $this->currentUser->rights === 9
            || ($this->currentUser->rights === 7 && $this->currentUser->rights > $profileUser->rights)
        ) {
            $buttons[] = ['url' => '/profile/' . $profileUser->id . '/edit', 'name' => __('Edit')];
        }
        if (! $isOwner && $this->currentUser->rights >= 7 && $this->currentUser->rights > $profileUser->rights) {
            $buttons[] = ['url' => '/admin/usr_del/?id=' . $profileUser->id, 'name' => __('Delete')];
        }
        if (! $isOwner && $this->currentUser->rights > $profileUser->rights) {
            $buttons[] = ['url' => '/profile/?act=ban&mod=do&user=' . $profileUser->id, 'name' => __('Ban')];
        }

        return $buttons;
    }

    /**
     * Relationship of the current user to the target: 0 - none, 1 - in contacts, 2 - in blocklist.
     */
    private function contactState(int $targetId): int
    {
        $contact = $this->contactRepository->findContact($this->currentUser->id, $targetId);
        if ($contact === null) {
            return 0;
        }

        return $contact->ban == 1 ? 2 : 1;
    }

    /**
     * Whether the target user has the current user in their blocklist.
     */
    private function isIgnored(int $targetId): bool
    {
        $contact = $this->contactRepository->findContact($targetId, $this->currentUser->id);

        return $contact !== null && $contact->ban == 1;
    }
}
