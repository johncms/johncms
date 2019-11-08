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

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('news', __DIR__ . '/templates/');

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
    $total = $db->query('SELECT COUNT(*) FROM `news`')->fetchColumn();
    $req = $db->query("SELECT * FROM `news` ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);

    function newsList($query)
    {
        global $tools, $db;

        while ($res = $query->fetch()) {
            $text = $tools->checkout($res['text'], 1, 1);
            $res['text'] = $tools->smilies($text, 1);

            if (! empty($res['kom'])) {
                $res_mes = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['kom'] . "'");

                if ($mes = $res_mes->fetch()) {
                    $res['kom_count'] = $mes['post_count'] - 1;
                } else {
                    $res['kom_count'] = 0;
                }
            }

            yield $res;
        }
    }

    echo $view->render('news::index', [
        'out'        => newsList($req),
        'pagination' => $tools->displayPagination('?', $start, $total, $user->config->kmess),
        'total'      => $total,
    ]);
}
