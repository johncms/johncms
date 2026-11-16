<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\UpdateForumSettingsCommand;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class ForumSettingsUseCase
{
    private const array DEFAULT_SETTINGS = [
        'upfp' => 0,
    ];

    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrent(User $user): array
    {
        // Only the keys the forum still reads: older accounts keep settings that were dropped since.
        return array_intersect_key(
            array_merge(self::DEFAULT_SETTINGS, (array) $user->set_forum),
            self::DEFAULT_SETTINGS
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function save(UpdateForumSettingsCommand $command, User $user): array
    {
        $settings = [
            'upfp' => $command->upfp,
        ];

        $this->profileUserRepository->saveForumSettings($user->id, $settings);

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function reset(User $user): array
    {
        $this->profileUserRepository->saveForumSettings($user->id, self::DEFAULT_SETTINGS);

        return self::DEFAULT_SETTINGS;
    }
}
