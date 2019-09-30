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

define('_IN_JOHNCMS', 1);

$act = isset($_GET['act']) ? trim($_GET['act']) : '';

$headmod = 'forumconverter';
require('../system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$textl = _t('Convert forum tables');
require('../system/head.php');
echo '<div class="phdr"><a href="index.php"><b>' . _t('Forum') . '</b></a> | ' . _t('Convert forum tables') . '</div>';

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

set_time_limit(3600);
ini_set('max_execution_time', 3600);
ini_set('memory_limit', '3G');

$structure = [];

if($systemUser->rights < 9) {
    exit('Access denied');
}

// Получаем первый уровень разделов
/*$first_sections = $db->query("SELECT * FROM forum where type = 'f'")->fetchAll();

foreach ($first_sections as $section) {


    $db->query("INSERT INTO forum_sections SET 
        parent = 0, 
        name = '".$section['text']."',
        description = '".$section['soft']."',
        sort = 100,
        access = '".$section['edit']."',
        section_type = 0,
        old_id = '".$section['id']."'
        ");

    $section_id = $db->lastInsertId();

    $subsections = $db->query("SELECT * FROM forum where type = 'r' AND refid = '".$section['id']."'")->fetchAll();

    foreach ($subsections as $subsection) {
        $db->query("INSERT INTO forum_sections SET 
        parent = '".$section_id."', 
        name = '".$subsection['text']."',
        description = '".$subsection['soft']."',
        sort = 100,
        access = '".$subsection['edit']."',
        section_type = 1,
        old_id = '".$subsection['id']."'
        ");
    }

}

 echo 'Структура перенесена';*/


/*$first_sections = $db->query("SELECT * FROM forum_sections where section_type = 1");



while ($section = $first_sections->fetch()) {

    p($section['name']);

    $topics = $db->query("SELECT * FROM forum where type = 't' AND refid = '".$section['old_id']."' ORDER BY id");

    while ($topic = $topics->fetch()) {
        $db->query("INSERT INTO forum_topic SET 
        section_id = '".$section['id']."', 
        name = '".$topic['text']."',
        user_id = '".$topic['user_id']."',
        user_name = '".$topic['from']."',
        pinned = ".($topic['vip'] == 1 ? 1 : 'NULL').",
        closed = ".($topic['edit'] == 1 ? 1 : 'NULL').",
        deleted = ".($topic['close'] == 1 ? 1 : 'NULL').",
        has_poll = ".($topic['realid'] == 1 ? 1 : 'NULL').",
        deleted_by = '".($topic['close_who'] ?? '')."',
        old_id = '".$topic['id']."'
        ");

    }


}

 echo 'Темы перенесены';*/

/*
$topics = $db->query("SELECT * FROM forum_topic");
while ($topic = $topics->fetch()) {
    $messages = $db->query("SELECT * FROM forum where type = 'm' AND refid = '".$topic['old_id']."' AND (migrate != 1 OR migrate IS NULL) ORDER BY id");
    while ($message = $messages->fetch()) {

        //p($message);

        $db->prepare('
          INSERT INTO `forum_messages` SET
          `topic_id` = ?,
          `text` = ?,
          `date` = ?,
          `user_id` = ?,
          `user_name` = ?,
          `user_agent` = ?,
          `ip` = ?,
          `ip_via_proxy` = ?,
          `pinned` = ?,
          `deleted` = ?,
          `deleted_by` = ?,
          `editor_name` = ?,
          `edit_time` = ?,
          `edit_count` = ?,
          `old_id` = ?
        ')->execute([
            $topic['id'],
            $message['text'],
            $message['time'],
            $message['user_id'],
            $message['from'],
            $message['soft'],
            $message['ip'],
            $message['ip_via_proxy'],
            ($message['vip'] == 1 ? 1 : 'NULL'),
            ($message['close'] == 1 ? 1 : 'NULL'),
            $message['close_who'],
            $message['edit'],
            $message['tedit'],
            $message['kedit'],
            $message['id']
        ]);

        $db->query("UPDATE forum SET migrate = 1 WHERE id = ". $message['id']);
    }
}*/



// Пересчет топиков
/*$topics = $db->query("SELECT * FROM forum_topic");
while ($topic = $topics->fetch()) {
    $tools->recountForumTopic($topic['id']);
}*/


// Обновляем файлы

/*$files = $db->query("SELECT * FROM `cms_forum_files`");
while ($file = $files->fetch()) {

    $cat = $db->query("SELECT * FROM forum_sections where old_id = '".$file['cat']."'")->fetch();
    $subcat = $db->query("SELECT * FROM forum_sections where old_id = '".$file['subcat']."'")->fetch();
    $topic = $db->query("SELECT * FROM forum_topic where old_id = '".$file['topic']."'")->fetch();
    $post = $db->query("SELECT * FROM forum_messages where old_id = '".$file['post']."'")->fetch();

    $db->query("UPDATE cms_forum_files SET
      cat = '".$cat['id']."',   
      subcat = '".$subcat['id']."',   
      topic = '".$topic['id']."',
      post = '".$post['id']."'
      WHERE id = ".$file['id']."
    ");
}*/


// Обновляем опросы

/*$votes = $db->query("SELECT * FROM `cms_forum_vote`");
while ($vote = $votes->fetch()) {
    $topic = $db->query("SELECT * FROM forum_topic where old_id = '".$vote['topic']."'")->fetch();
    $db->query("UPDATE cms_forum_vote SET 
      topic = '".$topic['id']."'
      WHERE id = ".$vote['id']."
    ");
}

$votes = $db->query("SELECT * FROM `cms_forum_vote_users`");
while ($vote = $votes->fetch()) {
    $topic = $db->query("SELECT * FROM forum_topic where old_id = '".$vote['topic']."'")->fetch();
    $db->query("UPDATE cms_forum_vote_users SET 
      topic = '".$topic['id']."'
      WHERE id = ".$vote['id']."
    ");
}*/


// Прописываем в таблицу редиректов новые адреса страниц
/*$items = $db->query("SELECT * FROM forum ORDER BY id");

$all_items = 0;
$links = 0;

while ($item = $items->fetch()) {
    $all_items++;
    $link = '';
    switch ($item['type']) {
        case 'f':
        case 'r':
            $section = $db->query("SELECT * FROM forum_sections WHERE old_id = ".$item['id'])->fetch();
            if(!empty($section)) {
                if(!empty($section['section_type'])) {
                    $link = '/forum/index.php?type=topics&id='.$section['id'];
                } else {
                    $link = '/forum/index.php?id='.$section['id'];
                }
            }
            break;

        case 't':
            $topic = $db->query("SELECT * FROM forum_topic WHERE old_id = ".$item['id'])->fetch();
            if(!empty($topic)) {
                $link = '/forum/index.php?type=topic&id='.$topic['id'];
            }
            break;

        case 'm':
            $message = $db->query("SELECT * FROM forum_messages WHERE old_id = ".$item['id'])->fetch();
            if(!empty($message)) {
                $link = '/forum/index.php?act=show_post&id='.$message['id'];
            }
            break;

    }

    if(!empty($link)) {
        $links++;
        $db->query("INSERT INTO forum_redirects SET old_id = ".$item['id'].", new_link = '".$link."'");
    }

}

echo 'Всего записей: '. $all_items.'<br>';
echo 'Ссылок для редиректов: '. $links.'<br>';*/



require('../system/end.php');
