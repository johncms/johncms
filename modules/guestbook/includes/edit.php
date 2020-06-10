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

// Edit post
if ($user->rights >= 6 && $id) {
    $errors = [];

    try {
        $message = (new Guestbook())->findOrFail($id);
    } catch (ModelNotFoundException $exception) {
        pageNotFound();
    }

    $form_data = [
        'message'    => $request->getPost('message', $message->text),
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
                    'text'       => $form_data['message'],
                    'edit_who'   => $user->name,
                    'edit_time'  => time(),
                    'edit_count' => ($message->edit_count + 1),
                ]
            );
            header('location: ./');
            exit;
        }

        $errors = $validator->getErrors();
    }

    echo $view->render(
        'guestbook::edit',
        [
            'id'      => $id,
            'message' => $message,
            'text'    => htmlspecialchars($form_data['message']),
            'errors'  => $errors,
            'bbcode'  => $bbcode->buttons('form', 'message'),
        ]
    );
}
