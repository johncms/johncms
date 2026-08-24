<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\SystemSettingsDTO;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;

final readonly class UpdateSystemSettingsUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(SystemSettingsDTO $dto): void
    {
        $config = $this->configRepository->getJohncms();

        $config['skindef'] = $dto->theme;
        $config['email'] = $dto->email;
        $config['timeshift'] = $dto->timeShift;
        $config['copyright'] = $dto->copyright;
        $config['homeurl'] = rtrim($dto->homeUrl, '/');
        $config['flsz'] = $dto->maxFileSize;
        $config['gzip'] = $dto->gzip;
        $config['meta_title'] = $dto->metaTitle;
        $config['meta_key'] = $dto->metaKeywords;
        $config['meta_desc'] = $dto->metaDescription;
        $config['user_email_required'] = $dto->userEmailRequired;
        $config['user_email_confirmation'] = $dto->userEmailConfirmation;
        $config['privacy_policy_url'] = $dto->privacyPolicyUrl;
        $config['terms_of_use_url'] = $dto->termsOfUseUrl;
        $config['personal_data_policy_url'] = $dto->personalDataPolicyUrl;
        $config['cookie_policy_url'] = $dto->cookiePolicyUrl;
        $config['mod_lib_comm'] = $dto->libraryComments;
        $config['mod_down_comm'] = $dto->downloadsComments;
        $config['registration_moderation'] = $dto->registrationModeration;

        $this->configRepository->saveJohncms($config);
    }
}
