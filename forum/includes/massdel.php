<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 3 || $rights >= 6) {
    // Массовое удаление выбранных постов форума
    require('../system/head.php');

    if (isset($_GET['yes'])) {
        $dc = $_SESSION['dc'];
        $prd = $_SESSION['prd'];

        /** @var PDO $db */
        $db = App::getContainer()->get(PDO::class);

        foreach ($dc as $delid) {
            $db->exec("UPDATE `forum` SET
                `close` = '1',
                `close_who` = '$login'
                WHERE `id` = '" . intval($delid) . "'
            ");
        }

        echo _t('Marked posts are deleted') . '<br><a href="' . $prd . '">' . _t('Back') . '</a><br>';
    } else {
        if (empty($_POST['delch'])) {
            echo '<p>' . _t('You did not choose something to delete') . '<br><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . _t('Back') . '</a></p>';
            require('../incfiles/end.php');
            exit;
        }

        foreach ($_POST['delch'] as $v) {
            $dc[] = intval($v);
        }

        $_SESSION['dc'] = $dc;
        $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
        echo '<p>' . _t('Do you really want to delete?') . '<br><a href="index.php?act=massdel&amp;yes">' . _t('Delete') . '</a> | ' .
            '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . _t('Cancel') . '</a></p>';
    }
}
