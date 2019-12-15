<?php

declare(strict_types=1);

/**
 * JohnCMS Content Management System (https://johncms.com)
 *
 * For copyright and license information, please see the LICENSE
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        https://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

use Johncms\System\Users\User;

define('_IN_JOHNCMS', 1);

require '../system/bootstrap.php';

/** @var Psr\Container\ContainerInterface $container */
$container = Johncms\System\Container\Factory::getContainer();

/** @var User $systemUser */
$systemUser = $container->get(User::class);

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var Johncms\System\Utility\Tools $tools */
$tools = $container->get(Johncms\System\Utility\Tools::class);

// TODO: Сделать перевод

/** @var PDO $db */
$db = $container->get(PDO::class);

@set_time_limit(3600);
@ini_set('max_execution_time', 3600);
@ini_set('memory_limit', '3G');

$step = $_REQUEST['step'] ?? '';

if ($systemUser->rights < 9) {
    header('Location: /');
    exit;
}

$textl = _t('Site update');
require '../system/head.php';
echo '<div class="phdr"><b>' . _t('Site update') . '</b></div>';

switch ($step) {
    // Создаем новые таблицы форума
    case 'create_tables':
        ?>
        <div class="rmenu">
            <?= _t('Create new tables'); ?>
        </div>
        <?php

        $db->exec(
            'CREATE TABLE `forum_messages`
            (
              `id`           bigint(20) NOT NULL,
              `topic_id`     bigint(20) NOT NULL,
              `text`         longtext   NOT NULL,
              `date`         int(11)      DEFAULT NULL,
              `user_id`      bigint(20) NOT NULL,
              `user_name`    varchar(255) DEFAULT NULL,
              `user_agent`   varchar(255) DEFAULT NULL,
              `ip`           bigint(20)   DEFAULT NULL,
              `ip_via_proxy` bigint(20)   DEFAULT NULL,
              `pinned`       tinyint(1)   DEFAULT NULL,
              `editor_name`  varchar(255) DEFAULT NULL,
              `edit_time`    int(11)      DEFAULT NULL,
              `edit_count`   int(11)      DEFAULT NULL,
              `deleted`      tinyint(1)   DEFAULT NULL,
              `deleted_by`   varchar(255) DEFAULT NULL,
              `old_id`       int(11)      DEFAULT NULL
            ) ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4;'
        );

        $db->exec(
            'CREATE TABLE `forum_redirects`
            (
              `old_id`   int(11)      NOT NULL,
              `new_link` varchar(255) NOT NULL
            ) ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4;'
        );

        $db->exec(
            "CREATE TABLE `forum_sections`
            (
              `id`           int(11)                         NOT NULL,
              `parent`       int(11) DEFAULT NULL,
              `name`         varchar(255) CHARACTER SET utf8 NOT NULL,
              `description`  text CHARACTER SET utf8,
              `sort`         int(11) DEFAULT '100',
              `access`       int(11) DEFAULT NULL,
              `section_type` int(11) DEFAULT NULL,
              `old_id`       int(11) DEFAULT NULL
            ) ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4;"
        );

        $db->exec(
            "CREATE TABLE `forum_topic`
            (
              `id`                        bigint(20) UNSIGNED NOT NULL,
              `section_id`                int(10) UNSIGNED    NOT NULL COMMENT 'Id родительского раздела',
              `name`                      varchar(255)        NOT NULL COMMENT 'Название темы',
              `description`               mediumtext COMMENT 'Краткое описание',
              `view_count`                bigint(20)   DEFAULT NULL COMMENT 'Количество просмотров',
              `user_id`                   bigint(20)          NOT NULL COMMENT 'Id автора темы',
              `user_name`                 varchar(255) DEFAULT NULL COMMENT 'Имя автора',
              `created_at`                datetime     DEFAULT NULL COMMENT 'Дата создания темы',
              `post_count`                int(11)      DEFAULT NULL COMMENT 'Количество постов',
              `mod_post_count`            int(11)      DEFAULT NULL COMMENT 'Количество постов с учетом удаленных',
              `last_post_date`            int(11)      DEFAULT NULL COMMENT 'Дата последнего поста',
              `last_post_author`          bigint(20)   DEFAULT NULL COMMENT 'id автора последнего поста',
              `last_post_author_name`     varchar(255) DEFAULT NULL COMMENT 'Имя автора последнего поста',
              `last_message_id`           bigint(20)   DEFAULT NULL COMMENT 'Id последнего сообщения',
              `mod_last_post_date`        int(11)      DEFAULT NULL COMMENT 'Дата последнего поста для модератора',
              `mod_last_post_author`      bigint(20)   DEFAULT NULL COMMENT 'id автора последнего поста для модератора',
              `mod_last_post_author_name` varchar(255) DEFAULT NULL COMMENT 'Имя автора последнего поста для модератора',
              `mod_last_message_id`       bigint(20)   DEFAULT NULL COMMENT 'Id последнего поста для модератора',
              `closed`                    tinyint(1)   DEFAULT NULL COMMENT 'Флаг закрытия темы',
              `closed_by`                 varchar(255) DEFAULT NULL COMMENT 'Имя закрывшего тему',
              `deleted`                   tinyint(1)   DEFAULT NULL COMMENT 'Флаг удаленной темы',
              `deleted_by`                varchar(255) DEFAULT NULL COMMENT 'Имя удалившего тему',
              `curators`                  mediumtext COMMENT 'Кураторы',
              `pinned`                    tinyint(1)   DEFAULT NULL COMMENT 'Флаг закрепленной темы',
              `has_poll`                  tinyint(1)   DEFAULT NULL COMMENT 'Флаг наличия опроса',
              `old_id`                    int(11)      DEFAULT NULL
            ) ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4;"
        );

        $db->exec(
            'ALTER TABLE `forum_messages`
              ADD PRIMARY KEY (`id`),
              ADD KEY `topic` (`topic_id`),
              ADD KEY `deleted` (`deleted`),
              ADD KEY `old_id` (`old_id`);
              ALTER TABLE `forum_messages`
              ADD FULLTEXT KEY `text` (`text`);
        '
        );

        $db->exec('ALTER TABLE `forum_redirects` ADD UNIQUE KEY `old_id` (`old_id`);');
        $db->exec('ALTER TABLE `forum_sections` ADD PRIMARY KEY (`id`), ADD KEY `parent` (`parent`), ADD KEY `old_id` (`old_id`);');
        $db->exec('ALTER TABLE `forum_topic` ADD PRIMARY KEY (`id`), ADD KEY `deleted` (`deleted`);');
        $db->exec('ALTER TABLE `forum_messages` MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

        $db->exec('ALTER TABLE `forum_sections` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

        $db->exec('ALTER TABLE `forum_topic` MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');

        ?>
        <div class="gmenu">
            <div style="margin-bottom: 5px;">
                <?= _t('Completed') ?>
            </div>
            <div>
                <form action="" method="get">
                    <input type="hidden" name="step" value="forum_structure">
                    <button type="submit"><?= _t('Next step') ?></button>
                </form>
            </div>
        </div>
        <?php

        break;

    // Переносим структуру форума
    case 'forum_structure':
        ?>
        <div class="rmenu">
            <?= _t('Convert the forum structure'); ?>
        </div>
        <?php

        $first_sections = $db->query("SELECT * FROM forum where type = 'f'")->fetchAll();
        foreach ($first_sections as $section) {
            $db->query(
                "INSERT INTO forum_sections SET
                parent = 0,
                name = '" . $section['text'] . "',
                description = '" . $section['soft'] . "',
                sort = 100,
                access = '" . $section['edit'] . "',
                section_type = 0,
                old_id = '" . $section['id'] . "'
            "
            );

            $section_id = $db->lastInsertId();
            $subsections = $db->query("SELECT * FROM forum where type = 'r' AND refid = '" . $section['id'] . "'")->fetchAll();
            foreach ($subsections as $subsection) {
                $db->query(
                    "INSERT INTO forum_sections SET
                    parent = '" . $section_id . "',
                    name = '" . $subsection['text'] . "',
                    description = '" . $subsection['soft'] . "',
                    sort = 100,
                    access = '" . $subsection['edit'] . "',
                    section_type = 1,
                    old_id = '" . $subsection['id'] . "'
                "
                );
            }
        }

        ?>
        <div class="gmenu">
            <div style="margin-bottom: 5px;">
                <?= _t('Completed') ?>
            </div>
            <div>
                <form action="" method="get">
                    <input type="hidden" name="step" value="forum_topics">
                    <button type="submit"><?= _t('Next step') ?></button>
                </form>
            </div>
        </div>
        <?php

        break;

    // Переносим темы форума
    case 'forum_topics':
        ?>
        <div class="rmenu">
            <?= _t('Convert forum topics'); ?>
        </div>
        <?php

        $for_step = 200;

        $start = ! empty($_SESSION['convert_topics']) ? (int) ($_SESSION['convert_topics']) : 0;
        $completed = false;
        $counter = 0;

        $topics = $db->query("SELECT * FROM forum where type = 't' ORDER BY id ASC LIMIT " . $start . ', ' . $for_step);
        while ($topic = $topics->fetch()) {
            // Получаем id нового раздела
            $new_section = $db->query("SELECT * FROM forum_sections where old_id = '" . $topic['refid'] . "'")->fetch();
            if (! empty($new_section)) {
                $db->query(
                    "INSERT INTO forum_topic SET
                    section_id = '" . $new_section['id'] . "',
                    name = '" . $topic['text'] . "',
                    user_id = '" . $topic['user_id'] . "',
                    user_name = '" . $topic['from'] . "',
                    pinned = " . ($topic['vip'] == 1 ? 1 : 'NULL') . ',
                    closed = ' . ($topic['edit'] == 1 ? 1 : 'NULL') . ',
                    deleted = ' . ($topic['close'] == 1 ? 1 : 'NULL') . ',
                    has_poll = ' . ($topic['realid'] == 1 ? 1 : 'NULL') . ",
                    deleted_by = '" . ($topic['close_who'] ?? '') . "',
                    old_id = '" . $topic['id'] . "'
                "
                );
            }

            $counter++;
        }

        if ($counter >= $for_step) {
            $_SESSION['convert_topics'] = $start + $for_step; ?>
            <div class="gmenu"><?= _t('Converted:') ?> <?= $_SESSION['convert_topics'] ?></div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                });
            </script>
            <?php

        } else {
            $completed = true;
        }
        if ($completed) {
            ?>
            <div class="gmenu">
                <div style="margin-bottom: 5px;">
                    <?= _t('Completed') ?>
                </div>
                <div>
                    <form action="" method="get">
                        <input type="hidden" name="step" value="forum_messages">
                        <button type="submit"><?= _t('Next step') ?></button>
                    </form>
                </div>
            </div>
            <?php

        }
        break;

    // Переносим сообщения форума
    case 'forum_messages':
        ?>
        <div class="rmenu">
            <?= _t('Convert forum messages'); ?>
        </div>
        <?php

        $start = ! empty($_SESSION['convert_messages']) ? (int) ($_SESSION['convert_messages']) : 0;
        $completed = false;
        $counter = 0;
        $for_step = 500;

        $messages = $db->query("SELECT * FROM forum where type = 'm' ORDER BY id ASC LIMIT " . $start . ', ' . $for_step);
        while ($message = $messages->fetch()) {
            $new_topic = $db->query("SELECT * FROM forum_topic where old_id = '" . $message['refid'] . "'")->fetch();
            if (! empty($new_topic)) {
                $db->prepare(
                    '
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
                '
                )->execute(
                    [
                        $new_topic['id'],
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
                        $message['id'],
                    ]
                );
            }
            $counter++;
        }

        if ($counter >= $for_step) {
            $_SESSION['convert_messages'] = $start + $for_step; ?>
            <div class="gmenu"><?= _t('Converted:') ?> <?= $_SESSION['convert_messages'] ?></div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    setTimeout(function () {
                        window.location.reload();
                    }, 200);
                });
            </script>
            <?php

        } else {
            $completed = true;
        }
        if ($completed) {
            ?>
            <div class="gmenu">
                <div style="margin-bottom: 5px;">
                    <?= _t('Completed') ?>
                </div>
                <div>
                    <form action="" method="get">
                        <input type="hidden" name="step" value="forum_topics_recount">
                        <button type="submit"><?= _t('Next step') ?></button>
                    </form>
                </div>
            </div>
            <?php

        }

        break;

    // Пересчет сообщений в темах
    case 'forum_topics_recount':
        ?>
        <div class="rmenu">
            <?= _t('Recount messages in topics'); ?>
        </div>
        <?php

        $start = ! empty($_SESSION['recount_topic']) ? (int) ($_SESSION['recount_topic']) : 0;
        $completed = false;
        $counter = 0;
        $for_step = 200;

        $topics = $db->query('SELECT * FROM forum_topic ORDER BY id ASC LIMIT ' . $start . ', ' . $for_step);
        while ($topic = $topics->fetch()) {
            $tools->recountForumTopic($topic['id']);
            $counter++;
        }

        if ($counter >= $for_step) {
            $_SESSION['recount_topic'] = $start + $for_step; ?>
            <div class="gmenu"><?= _t('Completed:') ?> <?= $_SESSION['recount_topic'] ?></div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                });
            </script>
            <?php

        } else {
            $completed = true;
        }
        if ($completed) {
            ?>
            <div class="gmenu">
                <div style="margin-bottom: 5px;">
                    <?= _t('Completed') ?>
                </div>
                <div>
                    <form action="" method="get">
                        <input type="hidden" name="step" value="forum_files">
                        <button type="submit"><?= _t('Next step') ?></button>
                    </form>
                </div>
            </div>
            <?php

        }
        break;

    // Обновление привязки файлов
    case 'forum_files':
        ?>
        <div class="rmenu">
            <?= _t('Update forum files table'); ?>
        </div>
        <?php

        $start = ! empty($_SESSION['forum_files_convert']) ? (int) ($_SESSION['forum_files_convert']) : 0;
        $completed = false;
        $counter = 0;
        $for_step = 400;

        $files = $db->query('SELECT * FROM `cms_forum_files` ORDER BY id ASC LIMIT ' . $start . ', ' . $for_step);
        while ($file = $files->fetch()) {
            $cat = $db->query("SELECT * FROM forum_sections where old_id = '" . $file['cat'] . "'")->fetch();
            $subcat = $db->query("SELECT * FROM forum_sections where old_id = '" . $file['subcat'] . "'")->fetch();
            $topic = $db->query("SELECT * FROM forum_topic where old_id = '" . $file['topic'] . "'")->fetch();
            $post = $db->query("SELECT * FROM forum_messages where old_id = '" . $file['post'] . "'")->fetch();

            $db->query(
                "UPDATE cms_forum_files SET
                cat = '" . $cat['id'] . "',
                subcat = '" . $subcat['id'] . "',
                topic = '" . $topic['id'] . "',
                post = '" . $post['id'] . "'
                WHERE id = " . $file['id'] . '
            '
            );
            $counter++;
        }

        if ($counter >= $for_step) {
            $_SESSION['forum_files_convert'] = $start + $for_step; ?>
            <div class="gmenu"><?= _t('Completed:') ?> <?= $_SESSION['forum_files_convert'] ?></div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                });
            </script>
            <?php

        } else {
            $completed = true;
        }
        if ($completed) {
            ?>
            <div class="gmenu">
                <div style="margin-bottom: 5px;">
                    <?= _t('Completed') ?>
                </div>
                <div>
                    <form action="" method="get">
                        <input type="hidden" name="step" value="forum_votes">
                        <button type="submit"><?= _t('Next step') ?></button>
                    </form>
                </div>
            </div>
            <?php

        }
        break;

    // Обновление привязки файлов
    case 'forum_votes':
        ?>
        <div class="rmenu">
            <?= _t('Update forum votes table'); ?>
        </div>
        <?php

        $start = ! empty($_SESSION['forum_votes_convert']) ? (int) ($_SESSION['forum_votes_convert']) : 0;
        $completed = false;
        $counter = 0;
        $for_step = 500;

        $votes = $db->query('SELECT * FROM `cms_forum_vote` ORDER BY id ASC LIMIT ' . $start . ', ' . $for_step);
        while ($vote = $votes->fetch()) {
            $topic = $db->query("SELECT * FROM forum_topic where old_id = '" . $vote['topic'] . "'")->fetch();
            $db->query(
                "UPDATE cms_forum_vote SET
              topic = '" . $topic['id'] . "'
              WHERE id = " . $vote['id'] . '
            '
            );
            $counter++;
        }

        if ($counter >= $for_step) {
            $_SESSION['forum_votes_convert'] = $start + $for_step; ?>
            <div class="gmenu"><?= _t('Completed:') ?> <?= $_SESSION['forum_votes_convert'] ?></div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                });
            </script>
            <?php

        } else {
            $completed = true;
        }
        if ($completed) {
            ?>
            <div class="gmenu">
                <div style="margin-bottom: 5px;">
                    <?= _t('Completed') ?>
                </div>
                <div>
                    <form action="" method="get">
                        <input type="hidden" name="step" value="forum_vote_users">
                        <button type="submit"><?= _t('Next step') ?></button>
                    </form>
                </div>
            </div>
            <?php

        }
        break;

    // Обновление привязки файлов
    case 'forum_vote_users':
        ?>
        <div class="rmenu">
            <?= _t('Update forum vote users table'); ?>
        </div>
        <?php

        $start = ! empty($_SESSION['forum_vote_users_convert']) ? (int) ($_SESSION['forum_vote_users_convert']) : 0;
        $completed = false;
        $counter = 0;
        $for_step = 1000;

        $votes = $db->query('SELECT * FROM `cms_forum_vote_users` ORDER BY id ASC LIMIT ' . $start . ', ' . $for_step);
        while ($vote = $votes->fetch()) {
            $topic = $db->query("SELECT * FROM forum_topic where old_id = '" . $vote['topic'] . "'")->fetch();
            $db->query(
                "UPDATE cms_forum_vote_users SET
              topic = '" . $topic['id'] . "'
              WHERE id = " . $vote['id'] . '
            '
            );
            $counter++;
        }

        if ($counter >= $for_step) {
            $_SESSION['forum_vote_users_convert'] = $start + $for_step; ?>
            <div class="gmenu"><?= _t('Completed:') ?> <?= $_SESSION['forum_vote_users_convert'] ?></div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                });
            </script>
            <?php

        } else {
            $completed = true;
        }
        if ($completed) {
            ?>
            <div class="gmenu">
                <div style="margin-bottom: 5px;">
                    <?= _t('Completed') ?>
                </div>
                <div>
                    <form action="" method="get">
                        <input type="hidden" name="step" value="forum_redirects">
                        <button type="submit"><?= _t('Next step') ?></button>
                    </form>
                </div>
            </div>
            <?php

        }
        break;

    // Настройка редиректов со старых ссылок на новые
    case 'forum_redirects':
        ?>
        <div class="rmenu">
            <?= _t('Setting redirects'); ?>
        </div>
        <?php

        $start = ! empty($_SESSION['forum_redirects']) ? (int) ($_SESSION['forum_redirects']) : 0;
        $completed = false;
        $counter = 0;
        $for_step = 5000;

        $items = $db->query('SELECT * FROM forum ORDER BY id ASC LIMIT ' . $start . ', ' . $for_step);
        while ($item = $items->fetch()) {
            $link = '';
            switch ($item['type']) {
                case 'f':
                case 'r':
                    $section = $db->query('SELECT * FROM forum_sections WHERE old_id = ' . $item['id'])->fetch();
                    if (! empty($section)) {
                        if (! empty($section['section_type'])) {
                            $link = '/forum/index.php?type=topics&id=' . $section['id'];
                        } else {
                            $link = '/forum/index.php?id=' . $section['id'];
                        }
                    }
                    break;

                case 't':
                    $topic = $db->query('SELECT * FROM forum_topic WHERE old_id = ' . $item['id'])->fetch();
                    if (! empty($topic)) {
                        $link = '/forum/index.php?type=topic&id=' . $topic['id'];
                    }
                    break;

                case 'm':
                    $message = $db->query('SELECT * FROM forum_messages WHERE old_id = ' . $item['id'])->fetch();
                    if (! empty($message)) {
                        $link = '/forum/index.php?act=show_post&id=' . $message['id'];
                    }
                    break;
            }

            if (! empty($link)) {
                $db->query('INSERT INTO forum_redirects SET old_id = ' . $item['id'] . ", new_link = '" . $link . "'");
            }

            $counter++;
        }

        if ($counter >= $for_step) {
            $_SESSION['forum_redirects'] = $start + $for_step; ?>
            <div class="gmenu"><?= _t('Completed:') ?> <?= $_SESSION['forum_redirects'] ?></div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                });
            </script>
            <?php

        } else {
            $completed = true;
        }
        if ($completed) {
            ?>
            <div class="gmenu">
                <div style="margin-bottom: 5px;">
                    <?= _t('Completed') ?>
                </div>
                <div>
                    <form action="" method="get">
                        <input type="hidden" name="step" value="clean_tables">
                        <button type="submit"><?= _t('Next step') ?></button>
                    </form>
                </div>
            </div>

            <?php

        }
        break;

    // Удаляме старые таблицы
    case 'clean_tables':
        ?>
        <div class="rmenu">
            <?= _t('Delete old tables'); ?>
        </div>
        <?php

        $db->exec('DROP TABLE `forum`');
        ?>
        <div class="gmenu">
            <div style="margin-bottom: 5px;">
                <?= _t('Update is finished') ?>
            </div>
            <div>
                <form action="/" method="get">
                    <button type="submit"><?= _t('Home page') ?></button>
                </form>
            </div>
        </div>
        <?php

        break;

    default:
        ?>
        <div class="rmenu">
            <div style="margin-bottom: 5px;">
                <?= _t('This wizard will convert tables to a new structure') ?><br>
                <b><?= _t('Be sure to back up before starting.') ?></b>
            </div>
            <form action="" method="get">
                <input type="hidden" name="step" value="create_tables">
                <button type="submit"><?= _t('Start') ?></button>
            </form>
        </div>
    <?php
}

require '../system/end.php';
