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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

// Рекламный блок сайта
if (! empty($cms_ads[2])) {
    echo '<div class="gmenu">' . $cms_ads[2] . '</div>';
}

echo '</div><div class="fmenu">';

if (isset($_GET['err']) || $headmod != 'mainpage' || ($headmod == 'mainpage' && isset($_GET['act']))) {
    echo '<div><a href=\'' . $config->homeurl . '\'>' . $tools->image('menu_home.png') . _t('Home', 'system') . '</a></div>';
}

echo '<div>' . $container->get('counters')->online() . '</div>' .
    '</div>' .
    '<div style="text-align:center">' .
    '<p><b>' . $config->copyright . '</b></p>';

// Счетчики каталогов
$req = $db->query('SELECT * FROM `cms_counters` WHERE `switch` = 1 ORDER BY `sort` ASC');

if ($req->rowCount()) {
    while ($res = $req->fetch()) {
        $link1 = ($res['mode'] == 1 || $res['mode'] == 2) ? $res['link1'] : $res['link2'];
        $link2 = $res['mode'] == 2 ? $res['link1'] : $res['link2'];
        $count = ($headmod == 'mainpage') ? $link1 : $link2;

        if (! empty($count)) {
            echo $count;
        }
    }
}

// Рекламный блок сайта
if (! empty($cms_ads[3])) {
    echo '<br />' . $cms_ads[3];
}

/*
-----------------------------------------------------------------
ВНИМАНИЕ!!!
Данный копирайт нельзя убирать в течение 90 дней с момента установки скриптов
-----------------------------------------------------------------
ATTENTION!!!
The copyright could not be removed within 90 days of installation scripts
-----------------------------------------------------------------
*/
echo '<div><small>&copy; <a href="http://johncms.com">JohnCMS</a></small></div>';

if ($systemUser->rights > 0) {
    $end_time = microtime(true);
    echo '<div style="margin-top: 3px;">' . round($end_time - START_TIME, 2) . ' сек.</div>';
}

echo '</div>
<script src="/theme/default/js/jquery-3.4.1.min.js"></script>
<script src="/theme/default/magnific_popup/jquery.magnific-popup.js"></script>
<script src="/theme/default/js/scripts.js"></script>
</body></html>';
