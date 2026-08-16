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

use Johncms\Mail\Exception\InvalidMailConfigurationException;

/**
 * Turns the mail configuration into a Symfony Mailer DSN.
 *
 * The DSN is the native language of the mailer: every transport it supports, including the ones
 * that come from a bridge package (Mailgun, SES, Postmark and the like) and the composite
 * `failover://` / `roundrobin://` schemes, is addressable by a string. So a site can write that
 * string directly, and the structured `transport` + `options` form is kept for configurations
 * written before the DSN existed and is compiled down to the same thing.
 */
final readonly class MailDsnResolver
{
    /** Transports expressible through the structured configuration form. */
    private const STRUCTURED_TRANSPORTS = ['smtp', 'sendmail', 'native', 'null'];

    /**
     * @param array<string, mixed> $config The `mail` configuration section.
     */
    public function resolve(array $config): string
    {
        $dsn = trim((string) ($config['dsn'] ?? ''));
        if ($dsn !== '') {
            return $dsn;
        }

        $transport = (string) ($config['transport'] ?? '');
        if ($transport === '') {
            throw new InvalidMailConfigurationException(
                'The mail configuration must define either "dsn" or "transport".'
            );
        }

        $options = $config['options'][$transport] ?? [];
        if (! is_array($options)) {
            throw new InvalidMailConfigurationException(
                sprintf('The options of the mail transport "%s" must be an array.', $transport)
            );
        }

        return match ($transport) {
            'smtp'     => $this->smtpDsn($options),
            'sendmail' => $this->sendmailDsn($options),
            'native'   => 'native://default',
            'null'     => 'null://null',

            default => throw new InvalidMailConfigurationException(
                sprintf(
                    'Unknown mail transport "%s". Use one of: %s, or set the "dsn" option instead.',
                    $transport,
                    implode(', ', self::STRUCTURED_TRANSPORTS)
                )
            ),
        };
    }

    /**
     * @param array<string, mixed> $options
     */
    private function smtpDsn(array $options): string
    {
        $host = trim((string) ($options['host'] ?? ''));
        if ($host === '') {
            $host = '127.0.0.1';
        }

        $port = (int) ($options['port'] ?? 0);
        $encryption = strtolower(trim((string) ($options['encryption'] ?? '')));

        // Port 465 speaks TLS from the first byte and never advertises STARTTLS, which is what the
        // `smtps` scheme means; on any other port an encrypted session is negotiated with STARTTLS,
        // and the mailer does that on its own whenever the server offers it.
        $scheme = ($encryption === 'ssl' || ($encryption === 'tls' && $port === 465)) ? 'smtps' : 'smtp';

        $credentials = '';
        $user = (string) ($options['username'] ?? '');
        $password = (string) ($options['password'] ?? '');
        if ($user !== '' || $password !== '') {
            $credentials = sprintf('%s:%s@', rawurlencode($user), rawurlencode($password));
        }

        $authority = $host;
        if ($port > 0) {
            $authority .= ':' . $port;
        }

        return $scheme . '://' . $credentials . $authority . $this->query($this->smtpQuery($options));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, string>
     */
    private function smtpQuery(array $options): array
    {
        $query = [];

        // Only turned off explicitly: an unset option must keep the secure default.
        if (array_key_exists('verify_peer', $options) && ! $options['verify_peer']) {
            $query['verify_peer'] = '0';
        }

        if (array_key_exists('auto_tls', $options) && ! $options['auto_tls']) {
            $query['auto_tls'] = '0';
        }

        if (! empty($options['require_tls'])) {
            $query['require_tls'] = '1';
        }

        $localDomain = trim((string) ($options['local_domain'] ?? ''));
        if ($localDomain !== '') {
            $query['local_domain'] = $localDomain;
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function sendmailDsn(array $options): string
    {
        $command = trim((string) ($options['command'] ?? ''));

        // Without a command the transport falls back to the `sendmail_path` of php.ini.
        return 'sendmail://default' . $this->query($command !== '' ? ['command' => $command] : []);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function query(array $parameters): string
    {
        return $parameters === [] ? '' : '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
}
