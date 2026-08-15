<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Users\User;

class UserProperties
{
    /** @var User */
    public $current_user;

    private StaffTitles $staffTitles;

    public function __construct()
    {
        $this->current_user = di(User::class);
        $this->staffTitles = di(StaffTitles::class);
    }

    /**
     * Метод подготавливает данные пользователя для шаблона
     *
     * @param array $user_data
     * @return array
     */
    public function getFromArray(array $user_data): array
    {
        $data_array = [];

        // Some callers pass a row of the users table, some a row that joined one; both name the
        // account, under a different key.
        $userId = (int) ($user_data['user_id'] ?? $user_data['id'] ?? 0);

        $data_array['user_profile_link'] = '';
        if ($userId > 0 && $this->current_user->id !== $userId && $this->current_user->isValid()) {
            $data_array['user_profile_link'] = '/profile/' . $userId;
        }

        $data_array['ip'] = long2ip((int) $user_data['ip']);
        $data_array['search_ip_url'] = '/admin/ip-search?ip=' . long2ip((int) $user_data['ip']);


        if (! empty($user_data['ip_via_proxy'])) {
            $data_array['ip_via_proxy'] = long2ip((int) $user_data['ip_via_proxy']);
            $data_array['search_ip_via_proxy_url'] = '/admin/ip-search?ip=' . long2ip((int) $user_data['ip_via_proxy']);
        } else {
            $data_array['ip_via_proxy'] = '';
            $data_array['search_ip_via_proxy_url'] = '';
        }

        $data_array['user_is_online'] = time() <= $user_data['lastdate'] + 300;
        $data_array['user_rights_name'] = $this->staffTitles->titleFor($userId);

        return $data_array;
    }
}
