<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$title = __('My Account');
$nav_chain->add($title);
// Проверяем права доступа
if ($user_data->id !== $user->id) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('Access forbidden'),
        ]
    );
    exit;
}

// Личный кабинет пользователя
$total_photo = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user->id . "'")->fetchColumn();

/** @var MailMessageRepositoryInterface $mailRepository */
$mailRepository = di(MailMessageRepositoryInterface::class);
/** @var ContactRepositoryInterface $contactRepository */
$contactRepository = di(ContactRepositoryInterface::class);

$data = [
    'counters' => [
        'total_photo'      => $total_photo,
        'inbox'            => $mailRepository->countInbox($user->id),
        'new_messages'     => $mailRepository->countNewInbox($user->id),
        'outbox'           => $mailRepository->countOutbox($user->id),
        'unread_sent'      => $mailRepository->countNewOutbox($user->id),
        'files'            => $mailRepository->countAttachedFiles($user->id),
        'contacts'         => $contactRepository->countContacts($user->id),
        'blocked_contacts' => $contactRepository->countBlocked($user->id),
    ],
];

echo $view->render(
    'profile::office',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
