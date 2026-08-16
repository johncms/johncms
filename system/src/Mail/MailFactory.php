<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class MailFactory
{
    private Mailer $mailer;
    private string $defaultFromEmail;
    private string $defaultFromName;
    private ?DkimSigner $dkimSigner = null;

    public function __invoke(ContainerInterface $container): self
    {
        $config = config('mail');
        $dsn = $container->get(MailDsnResolver::class)->resolve($config);

        // The http client is what the API transports of the provider bridges (Mailgun, Postmark,
        // SES and the like) send through, so it is handed over even when the site is on plain smtp.
        $transport = Transport::fromDsn(
            $dsn,
            client: $container->get(HttpClientInterface::class),
            logger: $container->get(LoggerInterface::class)
        );
        $this->mailer = new Mailer($transport);

        $site_config = config('johncms');
        $this->defaultFromEmail = $site_config['email'];
        $this->defaultFromName = $site_config['copyright'];

        $this->dkimSigner = $this->dkimSigner($config['dkim'] ?? [], $container->get(LoggerInterface::class));

        return $this;
    }

    /**
     * The DKIM signer of the site, or null when signing is not configured.
     *
     * A key that cannot be loaded is reported and then ignored: the mail of a site must not stop
     * because of it, and an unsigned message still arrives — it is only trusted less.
     *
     * @param array<string, mixed> $config
     */
    private function dkimSigner(array $config, LoggerInterface $logger): ?DkimSigner
    {
        $key = trim((string) ($config['private_key'] ?? ''));
        $domain = trim((string) ($config['domain'] ?? ''));
        $selector = trim((string) ($config['selector'] ?? ''));

        if ($key === '' || $domain === '' || $selector === '') {
            return null;
        }

        try {
            return new DkimSigner($key, $domain, $selector, passphrase: (string) ($config['passphrase'] ?? ''));
        } catch (Throwable $exception) {
            $logger->error('[MailFactory] The DKIM key could not be loaded; messages are sent unsigned', [
                'domain'    => $domain,
                'selector'  => $selector,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    public static function create(ContainerInterface $container)
    {
        return (new self())($container);
    }

    /**
     * Create a new message with the default sender of the site.
     */
    public function createEmail(): Email
    {
        $email = new Email();
        $email->from(sprintf('%s <%s>', $this->defaultFromName, $this->defaultFromEmail));
        return $email;
    }

    /**
     * Send a message.
     *
     * @throws TransportExceptionInterface
     */
    public function send(Email $email): void
    {
        $this->mailer->send($this->dkimSigner === null ? $email : $this->dkimSigner->sign($email));
    }
}
