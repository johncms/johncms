<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Profile\Application\Access\BanAccess;
use Johncms\Modules\Profile\Application\DTO\ProfileViewDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
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
        private AccessCheckerInterface $accessChecker,
        private BanAccess $banAccess,
        private CurrentUser $identity,
        private RoleLevels $roleLevels,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId): ProfileViewDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($profileUser === null || (! $profileUser->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        $config = config('johncms');
        $isOwner = $profileUser->id === $this->currentUser->id;
        $contactState = $this->contactState($profileUser->id);
        // Where the two of them stand relative to each other: it decides the buttons and whether
        // the address the account signed in from is shown at all
        $ownLevel = $this->roleLevels->highest($this->identity->identity());
        $targetLevel = $this->roleLevels->highestGrantedTo($profileUser->id);
        $targetStandsAbove = $targetLevel > $ownLevel;
        $targetStandsBelow = $targetLevel < $ownLevel;

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
            showIp: ! $targetStandsAbove && $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW),
            canWrite: ! $this->isIgnored($profileUser->id)
                && $contactState !== 2
                && ! isset($this->currentUser->ban['1'])
                && ! isset($this->currentUser->ban['3']),
            blocked: $contactState === 2,
            notifications: $notifications,
            activeBan: $activeBan,
            activeBanReason: $activeBanReason,
            counters: ['ban' => $profileUser->bans()->count()],
            buttons: $this->buildButtons($profileUser, $contactState, $targetStandsAbove, $targetStandsBelow),
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

        $data['positive_url'] = '/profile/' . $profileUser->id . '/karma?type=1';
        $data['negative_url'] = '/profile/' . $profileUser->id . '/karma';

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
                    $data['vote_url'] = '/profile/' . $profileUser->id . '/karma/vote';
                }
            }
        } else {
            $totalKarma = $this->karmaRepository->countVotesReceivedAfter($this->currentUser->id, time() - 86400);
            if ($totalKarma > 0) {
                $data['karma_new_url'] = '/profile/' . $this->currentUser->id . '/karma/new';
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
        if (! $profileUser->preg && empty($profileUser->regadm) && $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW)) {
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
    private function buildButtons(
        User $profileUser,
        int $contactState,
        bool $targetStandsAbove,
        bool $targetStandsBelow
    ): array {
        $buttons = [];
        $isOwner = $profileUser->id === $this->currentUser->id;

        if ($contactState !== 2) {
            $buttons[] = $contactState === 0
                ? ['url' => '/mail/add/' . $profileUser->id, 'name' => __('Add to Contacts')]
                : ['url' => '/mail/delete-contact/' . $profileUser->id, 'name' => __('Remove from Contacts')];
        }

        // Each button leads to a screen with a guard of its own, and asks here exactly what that
        // guard asks: a button nobody may press is worse than no button.
        if ($isOwner || (! $targetStandsAbove && $this->accessChecker->allows(ProfilePermissions::PROFILE_EDIT))) {
            $buttons[] = ['url' => '/profile/' . $profileUser->id . '/edit', 'name' => __('Edit')];
        }
        if (! $isOwner && ! $targetStandsAbove && $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE)) {
            $buttons[] = ['url' => '/admin/users/' . $profileUser->id . '/delete', 'name' => __('Delete')];
        }
        if (! $isOwner && $targetStandsBelow && $this->banAccess->mayBanAnything()) {
            $buttons[] = ['url' => '/profile/' . $profileUser->id . '/bans/new', 'name' => __('Ban')];
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
