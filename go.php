<?php

define('_IN_JOHNCMS', 1);

require('incfiles/core.php');

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();
$config = $container->get('config')['johncms'];

$referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config['homeurl'];
$url = isset($_REQUEST['url']) ? strip_tags(rawurldecode(trim($_REQUEST['url']))) : false;

if (isset($_GET['lng'])) {
    // Переключатель языков
    require('system/head.php');
    echo '<div class="menu"><form action="' . $referer . '" method="post"><p>';

    if (count(core::$lng_list) > 1) {
        echo '<p><h3>' . _t('Select language', 'system') . '</h3>';

        foreach (core::$lng_list as $key => $val) {
            echo '<div><input type="radio" value="' . $key . '" name="setlng" ' . ($key == core::$lng_iso ? 'checked="checked"' : '') . '/>&#160;' .
                (file_exists('images/flags/' . $key . '.gif') ? '<img src="images/flags/' . $key . '.gif" alt=""/>&#160;' : '') .
                $val .
                ($key == $config['lng'] ? ' <small class="red">[' . _t('Default', 'system') . ']</small>' : '') .
                '</div>';
        }

        echo '</p>';
    }

    echo '</p><p><input type="submit" name="submit" value="' . _t('Apply', 'system') . '" /></p><p><a href="' . $referer . '">' . _t('Back', 'system') . '</a></p></form></div>';
    require('system/end.php');
} elseif ($url) {
    // Редирект по ссылкам в текстах, обработанным функцией tags()
    if (isset($_POST['submit'])) {
        header('Location: ' . $url);
    } else {
        require('system/head.php');
        echo '<div class="phdr"><b>' . _t('External Link', 'system') . '</b></div>' .
            '<div class="rmenu">' .
            '<form action="go.php?url=' . rawurlencode($url) . '" method="post">' .
            '<p><h3>' . _t('ATTENTION!', 'system') . '</h3>' .
            _t('You are going to leave our site and go to an external link', 'system') . ':<br /><span class="red">' . htmlspecialchars($url) . '</span></p>' .
            '<p>' . _t('Administration of our site is not responsible for the content of external sites', 'system') . '.<br />' .
            sprintf(_t('It is recommended not to specify your data, relating to %s (Login, Password), on third party sites', 'system'), '<span class="green">' . $config['homeurl'] . '</span>') . '.</p>' .
            '<p><input type="submit" name="submit" value="' . _t('Go to Link', 'system') . '" /></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="' . $referer . '">' . _t('Back', 'system') . '</a></div>';
        require('system/end.php');
    }
} elseif ($id) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    // Редирект по рекламной ссылке
    $req = $db->query("SELECT * FROM `cms_ads` WHERE `id` = '$id'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $count_link = $res['count'] + 1;
        $db->exec("UPDATE `cms_ads` SET `count` = '$count_link'  WHERE `id` = '$id'");
        header('Location: ' . $res['link']);
    } else {
        header("Location: http://johncms.com/index.php?act=404");
    }
}
