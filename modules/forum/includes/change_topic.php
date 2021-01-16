<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\ForumUtils;
use Forum\Models\ForumTopic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\System\Http\Request;
use Johncms\Validator\Validator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 */

/** @var Request $request */
$request = di(Request::class);

$id = $request->getQuery('id', null, FILTER_VALIDATE_INT);

if ($user->rights !== 3 && $user->rights < 6) {
    ForumUtils::notFound();
}

$view->addData(['title' => __('Change the topic'), 'page_title' => __('Change the topic')]);

try {
    $current_topic = (new ForumTopic())->findOrFail($id);
} catch (ModelNotFoundException $exception) {
    echo $view->render(
        'system::pages/result',
        [
            'type'          => 'alert-danger',
            'message'       => $exception->getMessage(),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

$form_data = [
    'name'             => $request->getPost('name', $current_topic->name, FILTER_SANITIZE_STRING),
    'meta_keywords'    => $request->getPost('meta_keywords', $current_topic->meta_keywords, FILTER_SANITIZE_STRING),
    'meta_description' => $request->getPost('meta_description', $current_topic->meta_description, FILTER_SANITIZE_STRING),
    'csrf_token'       => $request->getPost('csrf_token', ''),
];

if ($request->getMethod() === 'POST') {
    $rules = [
        'name'       => [
            'NotEmpty',
            'StringLength'   => ['min' => 3, 'max' => 200],
            'ModelNotExists' => [
                'model'   => ForumTopic::class,
                'field'   => 'name',
                'exclude' => static function ($query) use ($current_topic, $id) {
                    $query->where('section_id', $current_topic->section_id)->where('id', '!=', $id);
                },
            ],
        ],
        'csrf_token' => ['Csrf'],
    ];

    $validator = new Validator($form_data, $rules);
    if ($validator->isValid()) {
        $current_topic->update($form_data);
        header("Location: ?type=topic&id=${id}");
        exit;
    }
    $errors = $validator->getErrors();
}

echo $view->render(
    'forum::change_topic',
    [
        'id'        => $id,
        'topic'     => $current_topic,
        'form_data' => $form_data,
        'back_url'  => '?type=topic&id=' . $id,
        'errors'    => $errors ?? [],
    ]
);
