<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Johncms;

class Cleanup
{
    public function __construct(\PDO $db)
    {
        $db->exec('DELETE FROM `cms_sessions` WHERE `lastdate` < ' . (time() - 86400));
        $db->exec("DELETE FROM `cms_users_iphistory` WHERE `time` < " . (time() - 7776000));
        $db->query('OPTIMIZE TABLE `cms_sessions`, `cms_users_iphistory`, `cms_mail`, `cms_contact`');

        return true;
    }
}
