<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Modules\Profile\Application\DTO\UpdateUserSettingsCommand;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class UserSettingsUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
    ) {
    }

    /**
     * Persist the personal settings, keeping any keys not managed by the form.
     *
     * @return string|null The selected language ISO code to apply to the session, or null when unchanged.
     */
    public function save(UpdateUserSettingsCommand $command, User $user): ?string
    {
        $settings = (new Collection($user->set_user))->toArray();

        $settings['timeshift'] = $this->clamp($command->timeshift, -12, 12);
        $settings['directUrl'] = $command->directUrl;
        $settings['youtube'] = $command->youtube;
        $settings['fieldHeight'] = $this->clamp(abs($command->fieldHeight), 1, 9);
        $settings['kmess'] = $this->clamp(abs($command->kmess), 5, 99);

        $selectedLng = null;
        $langList = config('johncms')['lng_list'] ?? [];
        if ($command->lng !== '' && array_key_exists($command->lng, $langList)) {
            $settings['lng'] = $command->lng;
            $selectedLng = $command->lng;
        }

        $this->profileUserRepository->saveUserSettings($user->id, $settings);

        return $selectedLng;
    }

    public function reset(User $user): void
    {
        $this->profileUserRepository->saveUserSettings($user->id, []);
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
