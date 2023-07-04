<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Community\Services;

use Johncms\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;

class CommunityService
{
    public function searchUsers(Request $request): array
    {
        $data['search'] = $request->getQuery('search');
        if ($data['search']) {
            $rules['search'] = [
                'StringLength' => ['min' => '2', 'max' => 300],
            ];
            $validator = new Validator($data, $rules);
            if ($validator->isValid()) {
                $users = User::query()->with(['activity', 'avatar'])
                    ->where('login', 'like', '%' . $data['search'] . '%')
                    ->orWhere('name', 'like', '%' . $data['search'] . '%')
                    ->orderBy('id')->paginate();
            } else {
                $errors = $validator->getErrors();
            }
        }

        return [
            'users'  => $users ?? null,
            'errors' => $errors ?? null,
            'search' => $data['search'],
        ];
    }
}
