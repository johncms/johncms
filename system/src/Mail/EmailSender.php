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

class EmailSender
{
    public static function send(int $message_count = 5): void
    {
        /** @var Render $view */
        $view = di(Render::class);

        /** @var MailFactory $mail */
        $mail = di(MailFactory::class);

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
            $message_body = $view->render($item->template, $item->fields);

            $mail->setTo($fields['email_to'], $fields['name_to'] ?? null);
            if (! empty($fields['subject'])) {
                $mail->setSubject($fields['subject']);
            }
            $mail->setHtmlBody($message_body);
            $mail->send();
            $item->update(['sent_at' => Carbon::now()]);
        }
    }
}
