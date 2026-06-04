<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\UpdateMailSettingsCommand;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class MailSettingsUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getCurrent(User $user): array
    {
        $settings = (array) $user->set_mail;
        if (! isset($settings['access'])) {
            $settings['access'] = 0;
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function save(UpdateMailSettingsCommand $command, User $user): array
    {
        $settings = (array) $user->set_mail;
        $settings['access'] = ($command->access >= 0 && $command->access <= 2) ? abs($command->access) : 0;

        $this->profileUserRepository->saveMailSettings($user->id, $settings);

        return $settings;
    }
}
