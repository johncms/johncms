<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Contacts\Application\DTO\ContactSettingsDTO;
use Johncms\Modules\Contacts\Application\DTO\SocialLinkDTO;

final readonly class UpdateContactSettingsUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(ContactSettingsDTO $dto): void
    {
        $config = $this->configRepository->getJohncms();

        $config['contacts_form_enabled'] = $dto->formEnabled;
        $config['contacts_notify_email'] = $dto->notifyEmail;
        $config['contacts_email'] = $dto->email;
        $config['contacts_phone'] = $dto->phone;
        $config['contacts_socials'] = array_map(
            static fn(SocialLinkDTO $link): array => ['title' => $link->title, 'url' => $link->url],
            $dto->socials
        );
        $config['contacts_address'] = $dto->addresses;
        $config['contacts_working_hours'] = $dto->workingHours;
        $config['contacts_text'] = $dto->texts;

        $this->configRepository->saveJohncms($config);
    }
}
