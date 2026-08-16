<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use Johncms\Mail\Exception\InvalidMailConfigurationException;
use Johncms\Mail\MailDsnResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport;

/**
 * The resolver is the only place where the mail settings of a site turn into something the mailer
 * understands, so the tests check both halves of that: the string it builds, and the fact that the
 * mailer actually accepts it — a DSN that looks right but names an option no transport reads would
 * fail silently on a live site.
 */
final class MailDsnResolverTest extends TestCase
{
    private function resolve(array $config): string
    {
        return (new MailDsnResolver())->resolve($config);
    }

    public function testExplicitDsnWins(): void
    {
        $dsn = $this->resolve(
            [
                'dsn'       => 'postmark+api://TOKEN@default',
                'transport' => 'smtp',
                'options'   => ['smtp' => ['host' => 'ignored.example.com']],
            ]
        );

        self::assertSame('postmark+api://TOKEN@default', $dsn);
    }

    public function testBlankDsnFallsBackToTheStructuredForm(): void
    {
        $dsn = $this->resolve(
            [
                'dsn'       => '   ',
                'transport' => 'smtp',
                'options'   => ['smtp' => ['host' => 'smtp.example.com', 'port' => 587]],
            ]
        );

        self::assertSame('smtp://smtp.example.com:587', $dsn);
    }

    // -----------------------------------------------------------------------------------
    // SMTP
    // -----------------------------------------------------------------------------------

    /**
     * Port 465 never advertises STARTTLS, so a configuration pointed at it has to produce the
     * implicit-TLS scheme; the old builder produced plain `smtp://` there and could not connect.
     */
    #[DataProvider('encryptionCases')]
    public function testEncryptionSelectsTheScheme(string $encryption, int $port, string $expectedScheme): void
    {
        $dsn = $this->resolve(
            [
                'transport' => 'smtp',
                'options'   => [
                    'smtp' => [
                        'host'       => 'smtp.example.com',
                        'port'       => $port,
                        'encryption' => $encryption,
                    ],
                ],
            ]
        );

        self::assertSame(sprintf('%s://smtp.example.com:%d', $expectedScheme, $port), $dsn);
    }

    public static function encryptionCases(): array
    {
        return [
            'ssl on the submission port' => ['ssl', 587, 'smtps'],
            'ssl on 465'                 => ['ssl', 465, 'smtps'],
            'tls on 465 is implicit tls' => ['tls', 465, 'smtps'],
            'tls on 587 is starttls'     => ['tls', 587, 'smtp'],
            'uppercase is accepted'      => ['TLS', 465, 'smtps'],
            'no encryption'              => ['', 25, 'smtp'],
        ];
    }

    public function testCredentialsAreEncoded(): void
    {
        $dsn = $this->resolve(
            [
                'transport' => 'smtp',
                'options'   => [
                    'smtp' => [
                        'host'     => 'smtp.example.com',
                        'port'     => 587,
                        'username' => 'mail@example.com',
                        'password' => 'p@ss:word/1',
                    ],
                ],
            ]
        );

        self::assertSame('smtp://mail%40example.com:p%40ss%3Aword%2F1@smtp.example.com:587', $dsn);

        // The mailer has to read back exactly what was put in, not the encoded form.
        $parsed = Transport\Dsn::fromString($dsn);
        self::assertSame('mail@example.com', $parsed->getUser());
        self::assertSame('p@ss:word/1', $parsed->getPassword());
    }

    /**
     * A relay that authenticates by user name alone used to lose its credentials entirely: the
     * old builder only kept them when both halves were filled in.
     */
    public function testUsernameWithoutPasswordIsKept(): void
    {
        $dsn = $this->resolve(
            [
                'transport' => 'smtp',
                'options'   => ['smtp' => ['host' => 'relay.example.com', 'username' => 'sender']],
            ]
        );

        self::assertSame('smtp://sender:@relay.example.com', $dsn);
        self::assertSame('sender', Transport\Dsn::fromString($dsn)->getUser());
    }

    public function testHostDefaultsToLocalhostAndThePortIsOptional(): void
    {
        self::assertSame('smtp://127.0.0.1', $this->resolve(['transport' => 'smtp']));
    }

    public function testStreamOptionsAreAddedOnlyWhenTheyChangeTheDefaults(): void
    {
        $config = [
            'transport' => 'smtp',
            'options'   => [
                'smtp' => [
                    'host'         => 'smtp.example.com',
                    'verify_peer'  => true,
                    'auto_tls'     => true,
                    'require_tls'  => false,
                    'local_domain' => '',
                ],
            ],
        ];

        self::assertSame('smtp://smtp.example.com', $this->resolve($config));

        $config['options']['smtp']['verify_peer'] = false;
        $config['options']['smtp']['auto_tls'] = false;
        $config['options']['smtp']['require_tls'] = true;
        $config['options']['smtp']['local_domain'] = 'example.com';

        $dsn = $this->resolve($config);
        $parsed = Transport\Dsn::fromString($dsn);
        self::assertSame('0', $parsed->getOption('verify_peer'));
        self::assertSame('0', $parsed->getOption('auto_tls'));
        self::assertSame('1', $parsed->getOption('require_tls'));
        self::assertSame('example.com', $parsed->getOption('local_domain'));
    }

    // -----------------------------------------------------------------------------------
    // The other transports
    // -----------------------------------------------------------------------------------

    public function testSendmailWithoutACommandLeavesItToPhpIni(): void
    {
        self::assertSame('sendmail://default', $this->resolve(['transport' => 'sendmail']));
    }

    public function testSendmailCommandSurvivesTheRoundTrip(): void
    {
        $dsn = $this->resolve(
            [
                'transport' => 'sendmail',
                'options'   => ['sendmail' => ['command' => '/usr/sbin/sendmail -bs -oi']],
            ]
        );

        self::assertSame('/usr/sbin/sendmail -bs -oi', Transport\Dsn::fromString($dsn)->getOption('command'));
    }

    public function testNativeAndNullTransports(): void
    {
        self::assertSame('native://default', $this->resolve(['transport' => 'native']));
        self::assertSame('null://null', $this->resolve(['transport' => 'null']));
    }

    // -----------------------------------------------------------------------------------
    // Broken configuration
    // -----------------------------------------------------------------------------------

    public function testAnEmptyConfigurationIsRejected(): void
    {
        $this->expectException(InvalidMailConfigurationException::class);
        $this->resolve([]);
    }

    public function testAnUnknownTransportNamesTheSupportedOnes(): void
    {
        $this->expectException(InvalidMailConfigurationException::class);
        $this->expectExceptionMessage('mailgun');
        $this->resolve(['transport' => 'mailgun']);
    }

    public function testMalformedOptionsAreRejected(): void
    {
        $this->expectException(InvalidMailConfigurationException::class);
        $this->resolve(['transport' => 'smtp', 'options' => ['smtp' => 'smtp.example.com']]);
    }

    // -----------------------------------------------------------------------------------
    // The shipped configuration
    // -----------------------------------------------------------------------------------

    /**
     * What a fresh installation gets must be a DSN the mailer can build a transport from.
     */
    public function testTheShippedConfigurationBuildsATransport(): void
    {
        $config = require CONFIG_PATH . 'autoload' . DS . 'mail.global.php';

        $dsn = $this->resolve($config['mail']);
        self::assertSame('sendmail://default', $dsn);
        self::assertInstanceOf(Transport\SendmailTransport::class, Transport::fromDsn($dsn));

        // And so must the smtp branch of the same file, which is what a site switches to.
        $config['mail']['transport'] = 'smtp';
        $smtpDsn = $this->resolve($config['mail']);
        self::assertStringStartsWith('smtps://', $smtpDsn);
        self::assertInstanceOf(Transport\Smtp\EsmtpTransport::class, Transport::fromDsn($smtpDsn));
    }
}
