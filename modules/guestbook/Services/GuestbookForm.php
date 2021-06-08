<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Guestbook\Services;

use Guestbook\Models\Guestbook;
use Johncms\System\Http\Request;
use Johncms\Users\User;

class GuestbookForm
{
    /** @var Request */
    protected $request;

    /** @var User */
    protected $user;

    public function __construct()
    {
        $this->request = di(Request::class);
        $this->user = di(User::class);
    }

    /**
     * @return array
     * @psalm-suppress PossiblyNullArgument
     */
    public function getFormData(): array
    {
        $form_data = [
            'name'       => $this->request->getPost('name', '', FILTER_SANITIZE_STRING),
            'message'    => $this->request->getPost('message', ''),
            'csrf_token' => $this->request->getPost('csrf_token', ''),
            'code'       => $this->request->getPost('code', ''),
        ];
        $form_data = array_map('trim', $form_data);
        $form_data['attached_files'] = (array) $this->request->getPost('attached_files', [], FILTER_VALIDATE_INT);

        return $form_data;
    }

    /**
     * @return array[]
     * @psalm-suppress MissingClosureReturnType,MissingClosureParamType
     */
    public function getValidationRules(): array
    {
        $rules = [
            'message'    => [
                'NotEmpty',
                'StringLength'   => ['min' => 4],
                'ModelNotExists' => [
                    'model'   => Guestbook::class,
                    'field'   => 'text',
                    'exclude' => function ($query) {
                        $query->where('user_id', $this->user->id)->where('time', '>', (time() - 600));
                    },
                ],
            ],
            'csrf_token' => [
                'Csrf',
                'Flood',
                'Ban' => [
                    'bans' => [1, 13],
                ],
            ],
        ];

        if (! $this->user->isValid()) {
            $rules['name'] = [
                'NotEmpty',
                'StringLength' => ['min' => 3, 'max' => 25],
            ];
            $rules['code'] = [
                'Captcha',
            ];
        }

        return $rules;
    }
}
