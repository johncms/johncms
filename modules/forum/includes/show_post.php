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

require 'system/head.php';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (empty($_GET['id'])) {
    echo $tools->displayError(_t('Wrong data'));
    require 'system/end.php';
    exit;
}

// Запрос сообщения
$res = $db->query("SELECT `forum_messages`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`
FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
WHERE `forum_messages`.`id` = '${id}'" . ($systemUser->rights >= 7 ? '' : " AND (`forum_messages`.`deleted` != '1' OR `forum_messages`.`deleted` IS NULL)") . ' LIMIT 1')->fetch();

// Запрос темы
$them = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
echo '<div class="phdr"><b>' . _t('Topic') . ':</b> ' . $them['name'] . '</div><div class="menu">';

// Данные пользователя
echo '<table cellpadding="0" cellspacing="0"><tr><td>';
if (file_exists(('../files/users/avatar/' . $res['user_id'] . '.png'))) {
    echo '<img src="../files/users/avatar/' . $res['user_id'] . '.png" width="32" height="32" alt="' . $res['user_name'] . '" />&#160;';
} else {
    echo '<img src="../images/empty.png" width="32" height="32" alt="' . $res['user_name'] . '" />&#160;';
}
echo '</td><td>';

if ($res['sex']) {
    echo $tools->image(($res['sex'] == 'm' ? 'm' : 'w') . ($res['datereg'] > time() - 86400 ? '_new' : '') . '.png', ['class' => 'icon-inline']);
} else {
    echo $tools->image('del.png');
}

// Ник юзера и ссылка на его анкету
if ($systemUser->isValid() && $systemUser->id != $res['user_id']) {
    echo '<a href="../profile/?user=' . $res['user_id'] . '"><b>' . $res['user_name'] . '</b></a> ';
} else {
    echo '<b>' . $res['user_name'] . '</b> ';
}

// Метка должности
$user_rights = [
    3 => '(FMod)',
    6 => '(Smd)',
    7 => '(Adm)',
    9 => '(SV!)',
];
echo @$user_rights[$res['rights']];

// Метка Онлайн / Офлайн
echo time() > $res['lastdate'] + 300 ? '<span class="red"> [Off]</span> ' : '<span class="green"> [ON]</span> ';
echo '<a href="index.php?act=show_post&amp;id=' . $res['id'] . '" title="Link to post">[#]</a>';

// Ссылки на ответ и цитирование
if ($systemUser->isValid() && $systemUser->id != $res['user_id']) {
    echo '&#160;<a href="index.php?act=say&type=reply&amp;id=' . $res['id'] . '&amp;start=' . $start . '">' . _t('[r]') . '</a>&#160;' .
        '<a href="index.php?act=say&type=reply&amp;id=' . $res['id'] . '&amp;start=' . $start . '&amp;cyt">' . _t('[q]') . '</a> ';
}

// Время поста
echo ' <span class="gray">(' . $tools->displayDate($res['date']) . ')</span><br />';

// Статус юзера
if (! empty($res['status'])) {
    echo '<div class="status">' . $tools->image('label.png', ['class' => 'icon-inline']) . $res['status'] . '</div>';
}

echo '</td></tr></table>';

// Вывод текста поста
$text = $tools->checkout($res['text'], 1, 1);
$text = $tools->smilies($text, ($res['rights'] >= 1) ? 1 : 0);
echo $text . '';

// Если есть прикрепленный файл, выводим его описание
$freq = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");

if ($freq->rowCount()) {
    $fres = $freq->fetch();
    $fls = round(@filesize('../files/forum/attach/' . $fres['filename']) / 1024, 2);
    echo '<div class="gray" style="font-size: x-small; background-color: rgba(128, 128, 128, 0.1); padding: 2px 4px; margin-top: 4px">' . _t('Attachment') . ':';
    // Предпросмотр изображений
    $att_ext = strtolower(pathinfo('./files/forum/attach/' . $fres['filename'], PATHINFO_EXTENSION));
    $pic_ext = [
        'gif',
        'jpg',
        'jpeg',
        'png',
    ];

    if (in_array($att_ext, $pic_ext)) {
        echo '<div><a class="image-preview" title="' . $fres['filename'] . '" data-source="index.php?act=file&amp;id=' . $fres['id'] . '" href="index.php?act=file&amp;id=' . $fres['id'] . '">';
        //TODO: thumbinal.php переместить в /assets
        echo '<img src="thumbinal.php?file=' . (urlencode($fres['filename'])) . '" alt="' . _t('Click to view image') . '" /></a></div>';
    } else {
        echo '<br /><a href="index.php?act=file&amp;id=' . $fres['id'] . '">' . $fres['filename'] . '</a>';
    }

    echo ' (' . $fls . ' кб.)<br>';
    echo _t('Downloads') . ': ' . $fres['dlcount'] . '</div>';
    $file_id = $fres['id'];
}

echo '</div>';

// Вычисляем, на какой странице сообщение?
$page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '${id}'")->fetchColumn() / $kmess);
echo '<div class="phdr"><a href="index.php?type=topic&id=' . $res['topic_id'] . '&amp;page=' . $page . '">' . _t('Back to topic') . '</a></div>';
echo '<p><a href="index.php">' . _t('Forum') . '</a></p>';
