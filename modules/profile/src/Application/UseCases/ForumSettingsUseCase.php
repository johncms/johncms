<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\UpdateForumSettingsCommand;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class ForumSettingsUseCase
{
    private const DEFAULT_SETTINGS = [
        'farea'    => 0,
        'upfp'     => 0,
        'preview'  => 1,
        'postclip' => 1,
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
        return array_merge(self::DEFAULT_SETTINGS, (array) $user->set_forum);
    }

    /**
     * @return array<string, mixed>
     */
    public function save(UpdateForumSettingsCommand $command, User $user): array
    {
        $postclip = ($command->postclip < 0 || $command->postclip > 2) ? 1 : $command->postclip;

        $settings = [
            'farea'    => $command->farea,
            'upfp'     => $command->upfp,
            'preview'  => $command->preview,
            'postclip' => $postclip,
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
