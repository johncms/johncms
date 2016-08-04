<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 3 || $rights >= 6) {
    // Массовое удаление выбранных постов форума
    require('../incfiles/head.php');

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
        echo $lng_forum['mass_delete_confirm'] . '<br/><a href="' . $prd . '">' . $lng['back'] . '</a><br/>';
    } else {
        if (empty($_POST['delch'])) {
            echo '<p>' . $lng_forum['error_mass_delete'] . '<br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . $lng['back'] . '</a></p>';
            require('../incfiles/end.php');
            exit;
        }

        foreach ($_POST['delch'] as $v) {
            $dc[] = intval($v);
        }

        $_SESSION['dc'] = $dc;
        $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
        echo '<p>' . $lng['delete_confirmation'] . '<br/><a href="index.php?act=massdel&amp;yes">' . $lng['delete'] . '</a> | ' .
            '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . $lng['cancel'] . '</a></p>';
    }
}
