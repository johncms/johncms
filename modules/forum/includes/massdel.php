<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
    // Массовое удаление выбранных постов форума
    require 'system/head.php';

    if (isset($_GET['yes'])) {
        $dc = $_SESSION['dc'];
        $prd = $_SESSION['prd'];

        if (! empty($dc)) {
            $db->exec("UPDATE `forum_messages` SET
                `deleted` = '1',
                `deleted_by` = '" . $systemUser->name . "'
                WHERE `id` IN (" . implode(',', $dc) . ')
            ');
        }

        echo _t('Marked posts are deleted') . '<br><a href="' . $prd . '">' . _t('Back') . '</a><br>';
    } else {
        if (empty($_POST['delch'])) {
            echo '<p>' . _t('You did not choose something to delete') . '<br><a href="' . htmlspecialchars(getenv('HTTP_REFERER')) . '">' . _t('Back') . '</a></p>';
            require 'system/end.php';
            exit;
        }

        foreach ($_POST['delch'] as $v) {
            $dc[] = (int) $v;
        }

        $_SESSION['dc'] = $dc;
        $_SESSION['prd'] = htmlspecialchars(getenv('HTTP_REFERER'));
        echo '<p>' . _t('Do you really want to delete?') . '<br><a href="?act=massdel&amp;yes">' . _t('Delete') . '</a> | ' .
            '<a href="' . htmlspecialchars(getenv('HTTP_REFERER')) . '">' . _t('Cancel') . '</a></p>';
    }
}
