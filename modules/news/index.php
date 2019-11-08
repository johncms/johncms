<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var ContainerInterface $container
 * @var PDO                $db
 * @var ToolsInterface     $tools
 * @var UserInterface      $user
 * @var Engine             $view
 */

$container = App::getContainer();
$db = $container->get(PDO::class);
$tools = $container->get(ToolsInterface::class);
$user = $container->get(UserInterface::class);
$view = $container->get(Engine::class);

// Регистрируем языки модуля
$container->get(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

$actions = [
    'add',
    'clean',
    'del',
    'edit',
];

if (($key = array_search($act, $actions)) !== false) {
    ob_start(); // Перехват вывода скриптов без шаблона
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
    echo $view->render('system::app/old_content', [
        'title'   => _t('News'),
        'content' => ob_get_clean(),
    ]);
} else {
    ob_start(); // Перехват вывода скриптов без шаблона

    // Вывод списка новостей
    echo '<div class="phdr"><b>' . _t('News') . '</b></div>';

    if ($user->rights >= 6) {
        echo '<div class="topmenu"><a href="?do=add">' . _t('Add') . '</a> | <a href="?do=clean">' . _t('Clear') . '</a></div>';
    }

    $total = $db->query('SELECT COUNT(*) FROM `news`')->fetchColumn();
    $req = $db->query("SELECT * FROM `news` ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);
    $i = 0;

    while ($res = $req->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $text = $tools->checkout($res['text'], 1, 1);
        $text = $tools->smilies($text, 1);
        echo '<h3>' . $res['name'] . '</h3>' .
            '<span class="gray"><small>' . _t('Author') . ': ' . $res['avt'] . ' (' . $tools->displayDate($res['time']) . ')</small></span>' .
            '<br />' . $text . '<div class="sub">';

        if ($res['kom'] != 0 && $res['kom'] != '') {
            $res_mes = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['kom'] . "'");
            $komm = 0;
            if ($mes = $res_mes->fetch()) {
                $komm = $mes['post_count'] - 1;
            }
            if ($komm >= 0) {
                echo '<a href="../forum/?type=topic&id=' . $res['kom'] . '">' . _t('Discuss in Forum') . ' (' . $komm . ')</a><br>';
            }
        }

        if ($user->rights >= 6) {
            echo '<a href="?do=edit&amp;id=' . $res['id'] . '">' . _t('Edit') . '</a> | ' .
                '<a href="?do=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>';
        }

        echo '</div></div>';
        ++$i;
    }
    echo '<div class="phdr">' . _t('Total') . ':&#160;' . $total . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $user->config->kmess) . '</div>' .
            '<p><form method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
    }

    echo $view->render('system::app/old_content', [
        'title'   => _t('News'),
        'content' => ob_get_clean(),
    ]);
}
