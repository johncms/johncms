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

use Interop\Container\ContainerInterface;

class Cleanup
{
    private $cacheFile = 'cleanup.dat';

    public function __invoke(ContainerInterface $container)
    {
        /** @var \PDO $db */
        $db = $container->get(\PDO::class);
        $file = CACHE_PATH . $this->cacheFile;

        if (!file_exists($file) || filemtime($file) < (time() - 86400)) {
            $db->exec('DELETE FROM `cms_sessions` WHERE `lastdate` < ' . (time() - 86400));
            $db->exec("DELETE FROM `cms_users_iphistory` WHERE `time` < " . (time() - 7776000));
            $db->query('OPTIMIZE TABLE `cms_sessions`, `cms_users_iphistory`, `cms_mail`, `cms_contact`');

            file_put_contents($file, time());
        }

        return true;
    }
}
