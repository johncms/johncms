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

use Carbon\Carbon;
use Gettext\TranslatorFunctions;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class EmailSender
{
    public static function send(int $message_count = 5): void
    {
        /** @var Render $view */
        $view = di(Render::class);

        /** @var MailFactory $mailFactory */
        $mailFactory = di(MailFactory::class);

        /** @var LoggerInterface $logger */
        $logger = di(LoggerInterface::class);

        $email = (new EmailMessage())->unsent()->orderBy('priority')->limit($message_count)->get();

        foreach ($email as $item) {
            /** @var EmailMessage $item */
            $fields = $item->fields;

            if (empty($fields['email_to']) || empty($item->template)) {
                $item->update(['sent_at' => Carbon::now()]);
                continue;
            }

            $translator = new Translator();
            $translator->setLocale($item->locale);
            $translator->addTranslationDomain('system', ROOT_PATH . 'system/locale');
            TranslatorFunctions::register($translator);
            $view->addData(['locale' => $item->locale]);

            // Per-message isolation. Render failures used to be swallowed by Render itself and
            // the exception message was mailed out as the message body; now they propagate, and
            // without this guard one unrenderable row would abort the batch on every run and
            // block the whole queue for good. The row is marked as handled — the same way an
            // undeliverable row is treated above — so a template that cannot render is not
            // retried forever.
            try {
                $message_body = $view->render($item->template, $item->fields);
            } catch (Throwable $exception) {
                $logger->error(
                    'Unable to render the email template',
                    [
                        'template'  => $item->template,
                        'message_id' => $item->id,
                        'exception' => $exception,
                    ]
                );
                $item->update(['sent_at' => Carbon::now()]);
                continue;
            }

            // In some cases, using the @ symbol in the sender's name resulted in an error.
            if (str_contains($fields['name_to'], '@')) {
                $fields['name_to'] = null;
            }

            $email = $mailFactory->createEmail();

            if ($fields['name_to']) {
                $email->to(sprintf('%s <%s>', $fields['name_to'], $fields['email_to']));
            } else {
                $email->to($fields['email_to']);
            }

            if (! empty($fields['subject'])) {
                $email->subject($fields['subject']);
            }

            $email->html($message_body);

            try {
                $mailFactory->send($email);
            } catch (TransportExceptionInterface $e) {
                $logger->error(
                    sprintf('[EmailSender] Failed to send email to %s', $fields['email_to']),
                    ['exception' => $e]
                );
            }

            $item->update(['sent_at' => Carbon::now()]);
        }
    }
}
