<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Guestbook\Models\Guestbook;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Bbcode;
use Johncms\Users\User;
use Johncms\Validator\Validator;

/** @var User $user */
$user = di(User::class);

/** @var Request $request */
$request = di(Request::class);

/** @var Bbcode $bbcode */
$bbcode = di(Bbcode::class);

// Add "admin response"
if ($user->rights >= 6 && $id) {
    $errors = [];

    try {
        $message = (new Guestbook())->findOrFail($id);
    } catch (ModelNotFoundException $exception) {
        pageNotFound();
    }

    $form_data = [
        'message'    => $request->getPost('message', $message->otvet),
        'csrf_token' => $request->getPost('csrf_token', ''),
    ];

    if ($request->getMethod() === 'POST') {
        $rules = [
            'message'    => [
                'NotEmpty',
                'StringLength' => ['min' => 4, 'max' => 16000],
            ],
            'csrf_token' => [
                'Csrf',
            ],
        ];

        $validator = new Validator($form_data, $rules);
        if ($validator->isValid()) {
            $message->update(
                [
                    'otvet' => $form_data['message'],
                    'admin' => $user->name,
                    'otime' => time(),
                ]
            );
            header('location: ./');
            exit;
        }

        $errors = $validator->getErrors();
    }

    echo $view->render(
        'guestbook::reply',
        [
            'id'         => $id,
            'message'    => $message,
            'errors'     => $errors,
            'reply_text' => htmlspecialchars($message->reply_text),
            'bbcode'     => $bbcode->buttons('form', 'message'),
        ]
    );
}
