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

use Johncms\System\Users\User;

class UserProperties
{

    /** @var User */
    public $current_user;

    public function __construct()
    {
        $this->current_user = di(User::class);
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

        $data_array['user_profile_link'] = '';
        if (isset($user_data['user_id']) && $this->current_user->id !== $user_data['user_id'] && $this->current_user->isValid()) {
            $data_array['user_profile_link'] = '/profile/?user=' . $user_data['user_id'];
        }

        $data_array['ip'] = long2ip((int) $user_data['ip']);
        $data_array['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip((int) $user_data['ip']);


        if (! empty($user_data['ip_via_proxy'])) {
            $data_array['ip_via_proxy'] = long2ip((int) $user_data['ip_via_proxy']);
            $data_array['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . long2ip((int) $user_data['ip_via_proxy']);
        } else {
            $data_array['ip_via_proxy'] = '';
            $data_array['search_ip_via_proxy_url'] = '';
        }

        $data_array['user_is_online'] = time() <= $user_data['lastdate'] + 300;
        $rights = isset($user_data['rights']) ? (int) $user_data['rights'] : 0;
        $data_array['user_rights_name'] = $this->getRightsName($rights);

        return $data_array;
    }

    /**
     * @param int $rights
     * @return string
     */
    public function getRightsName(int $rights): string
    {
        $user_rights_names = [
            3 => d__('system', 'Forum moderator'),
            4 => d__('system', 'Download moderator'),
            5 => d__('system', 'Library moderator'),
            6 => d__('system', 'Super moderator'),
            7 => d__('system', 'Administrator'),
            9 => d__('system', 'Supervisor'),
        ];

        return array_key_exists($rights, $user_rights_names) ? $user_rights_names[$rights] : '';
    }
}
