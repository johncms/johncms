<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Mail\Exception\InvalidEmailAddressException;
use Johncms\Mail\Exception\InvalidMailConfigurationException;
use Johncms\Modules\Admin\Application\DTO\MailSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\SendTestEmailUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateMailSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final readonly class MailSettingsController
{
    private const URL = '/admin/settings/mail';

    public function __construct(
        private NavChain $navChain,
        private UpdateMailSettingsUseCase $updateMailSettings,
        private SendTestEmailUseCase $sendTestEmail,
        private Session $session,
    ) {
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
        try {
            $this->updateMailSettings->execute($this->buildDto($request));
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `mail.local.php`'));
        } catch (InvalidMailConfigurationException $exception) {
            return $this->renderForm($exception->getMessage());
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    /**
     * The settings are read while the container is compiled, so the message is sent by the request
     * that follows the save rather than by the save itself — by then the new settings are in force.
     */
    public function test(Request $request): ViewResponse
    {
        $recipient = trim($request->body('test_email', ''));

        try {
            $this->sendTestEmail->execute(
                $recipient,
                __('Test message'),
                __('If you are reading this, the mail settings of your website are working.')
            );
        } catch (InvalidEmailAddressException) {
            return $this->renderForm(__('Invalid Email address'));
        } catch (TransportExceptionInterface $exception) {
            // The words of the mail server itself: without them there is nothing to act on.
            return $this->renderForm(
                sprintf('%s %s', __('The test message could not be sent.'), $exception->getMessage())
            );
        }

        $this->session->flash('success_message', sprintf(__('The test message was sent to %s.'), $recipient));
        redirect(self::URL);
    }

    private function buildDto(Request $request): MailSettingsDTO
    {
        return new MailSettingsDTO(
            dsn: trim($request->body('dsn', '')),
            transport: $request->body('transport', 'sendmail'),
            host: trim($request->body('host', '')),
            port: $request->bodyInt('port'),
            username: trim($request->body('username', '')),
            password: $request->body('password', ''),
            encryption: $request->body('encryption', ''),
            sendmailCommand: trim($request->body('sendmail_command', '')),
            redirectTo: trim($request->body('redirect_to', '')),
        );
    }

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('Mail Settings');
        $this->navChain->add($title);

        $config = config('mail') ?? [];
        $smtp = $config['options']['smtp'] ?? [];
        $redirectTo = $config['redirect_to'] ?? '';

        return new ViewResponse(
            '@admin/mail-settings.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['mail_settings' => true],
                'settings'   => [
                    'dsn'              => (string) ($config['dsn'] ?? ''),
                    'transport'        => (string) ($config['transport'] ?? 'sendmail'),
                    'host'             => (string) ($smtp['host'] ?? ''),
                    'port'             => (int) ($smtp['port'] ?? 0),
                    'username'         => (string) ($smtp['username'] ?? ''),
                    'password'         => (string) ($smtp['password'] ?? ''),
                    'encryption'       => (string) ($smtp['encryption'] ?? ''),
                    'sendmail_command' => (string) ($config['options']['sendmail']['command'] ?? ''),
                    // A list is shown as the comma separated form the field takes.
                    'redirect_to'      => is_array($redirectTo) ? implode(', ', $redirectTo) : (string) $redirectTo,
                ],
                'transports'      => ['smtp', 'sendmail', 'native', 'null'],
                'encryptions'     => ['', 'ssl', 'tls'],
                'test_email'      => (string) config('johncms.email', ''),
                'form_action'     => self::URL,
                'test_action'     => self::URL . '/test',
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
