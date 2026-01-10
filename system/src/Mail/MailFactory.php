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
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class MailFactory
{
    private Mailer $mailer;
    private string $defaultFromEmail;
    private string $defaultFromName;

    public function __invoke(ContainerInterface $container): self
    {
        $config = config('mail');

        $dsn = $this->buildDsn($config);
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);

        $site_config = config('johncms');
        $this->defaultFromEmail = $site_config['email'];
        $this->defaultFromName = $site_config['copyright'];

        return $this;
    }

    /**
     * Создать новый Email с default from и UTF-8
     */
    public function createEmail(): Email
    {
        $email = new Email();
        $email->from(sprintf('%s <%s>', $this->defaultFromName, $this->defaultFromEmail));
        return $email;
    }

    /**
     * Отправка письма
     *
     * @throws TransportExceptionInterface
     */
    public function send(Email $email): void
    {
        $this->mailer->send($email);
    }

    private function buildDsn(array $config): string
    {
        $transport = $config['transport'];
        $options = $config['options'][$transport] ?? [];

        return match ($transport) {
            'smtp' => $this->smtpDsn($options),
            'sendmail' => $this->sendmailDsn($options),

            default => throw new \RuntimeException(
                sprintf('Unknown mail transport "%s"', $transport)
            ),
        };
    }

    private function smtpDsn(array $opt): string
    {
        $host = $opt['host'] ?? '127.0.0.1';
        $port = $opt['port'] ?? 25;

        $user = $opt['username'] ?? null;
        $pass = $opt['password'] ?? null;

        $enc = $opt['encryption'] ?? null;
        $mode = $opt['auth_mode'] ?? null;

        if ($user && $pass) {
            return sprintf(
                'smtp://%s:%s@%s:%d?encryption=%s&auth_mode=%s',
                rawurlencode($user),
                rawurlencode($pass),
                $host,
                $port,
                $enc ?? 'null',
                $mode ?? 'null'
            );
        }

        return sprintf('smtp://%s:%d', $host, $port);
    }

    private function sendmailDsn(array $opt): string
    {
        $cmd = $opt['command'] ?? '/usr/sbin/sendmail -bs';
        return sprintf('sendmail://default?command=%s', urlencode($cmd));
    }
}
