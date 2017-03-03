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

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
    if (!$id) {
        require('../system/head.php');
        echo $tools->displayError(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }

    $typ = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't'");

    if (!$typ->rowCount()) {
        require('../system/head.php');
        echo $tools->displayError(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }

    if (isset($_POST['submit'])) {
        $razd = isset($_POST['razd']) ? abs(intval($_POST['razd'])) : false;

        if (!$razd) {
            require('../system/head.php');
            echo $tools->displayError(_t('Wrong data'));
            require('../system/end.php');
            exit;
        }

        $typ1 = $db->query("SELECT * FROM `forum` WHERE `id` = '$razd' AND `type` = 'r'");

        if (!$typ1->rowCount()) {
            require('../system/head.php');
            echo $tools->displayError(_t('Wrong data'));
            require('../system/end.php');
            exit;
        }

        $db->exec("UPDATE `forum` SET
            `refid` = '$razd'
            WHERE `id` = '$id'
        ");
        header("Location: index.php?id=$id");
    } else {
        // Перенос темы
        $ms = $typ->fetch();
        require('../system/head.php');

        if (empty($_GET['other'])) {
            $rz1 = $db->query("SELECT * FROM `forum` WHERE id='" . $ms['refid'] . "'")->fetch();
            $other = $rz1['refid'];
        } else {
            $other = intval($_GET['other']);
        }

        $fr1 = $db->query("SELECT * FROM `forum` WHERE id='" . $other . "'")->fetch();
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Move Topic') . '</div>' .
            '<form action="index.php?act=per&amp;id=' . $id . '" method="post">' .
            '<div class="gmenu"><p>' .
            '<h3>' . _t('Category') . '</h3>' . $fr1['text'] . '</p>' .
            '<p><h3>' . _t('Section') . '</h3>' .
            '<select name="razd">';
        $raz = $db->query("SELECT * FROM `forum` WHERE `refid` = '$other' AND `type` = 'r' AND `id` != '" . $ms['refid'] . "' ORDER BY `realid` ASC");

        while ($raz1 = $raz->fetch()) {
            echo '<option value="' . $raz1['id'] . '">' . $raz1['text'] . '</option>';
        }

        echo '</select></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Move') . '"/></p>' .
            '</div></form>' .
            '<div class="phdr">' . _t('Other categories') . '</div>';
        $frm = $db->query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$other' ORDER BY `realid` ASC");

        while ($frm1 = $frm->fetch()) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo '<a href="index.php?act=per&amp;id=' . $id . '&amp;other=' . $frm1['id'] . '">' . $frm1['text'] . '</a></div>';
            ++$i;
        }

        echo '<div class="phdr"><a href="index.php">' . _t('Back') . '</a></div>';
    }
}
