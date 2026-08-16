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
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailFactory
{
    private Mailer $mailer;
    private string $defaultFromEmail;
    private string $defaultFromName;

    public function __invoke(ContainerInterface $container): self
    {
        $dsn = $container->get(MailDsnResolver::class)->resolve(config('mail'));

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

        return $this;
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
        $this->mailer->send($email);
    }
}
