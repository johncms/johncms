<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Mail\Exception\InvalidMailConfigurationException;
use Johncms\Mail\MailDsnResolver;
use Johncms\Modules\Admin\Application\DTO\MailSettingsDTO;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\MailConfigRepositoryInterface;

final readonly class UpdateMailSettingsUseCase
{
    public function __construct(
        private MailConfigRepositoryInterface $configRepository,
        private MailDsnResolver $dsnResolver,
    ) {
    }

    /**
     * @throws ConfigWriteException
     * @throws InvalidMailConfigurationException When the settings do not describe a transport the
     *                                           mailer could build. Saving them would leave the
     *                                           site unable to send anything at all.
     */
    public function execute(MailSettingsDTO $dto): void
    {
        $config = $this->configRepository->getMail();

        $config['dsn'] = $dto->dsn;
        $config['transport'] = $dto->transport;
        $config['redirect_to'] = $this->addressList($dto->redirectTo);

        $config['options']['smtp'] = [
            'host'       => $dto->host,
            'port'       => $dto->port,
            'username'   => $dto->username,
            'password'   => $dto->password,
            'encryption' => $dto->encryption,
        ];

        $config['options']['sendmail'] = ['command' => $dto->sendmailCommand];

        // Checked before it is written: a configuration the resolver refuses would take the mail
        // of the site down until somebody edited the file by hand.
        $this->dsnResolver->resolve($config);

        $this->configRepository->saveMail($config);
    }

    /**
     * The field takes addresses separated by commas; the configuration holds them as a list, so
     * that a second address is never read as part of the first.
     *
     * @return list<string>
     */
    private function addressList(string $value): array
    {
        $addresses = [];

        foreach (explode(',', $value) as $address) {
            $address = trim($address);
            if ($address !== '') {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }
}
