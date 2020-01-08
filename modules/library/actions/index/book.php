<?php

use Library\Hashtags;
use Library\Rating;
use Library\Utils;

$row = $db->query('SELECT * FROM `library_texts` WHERE `id`=' . $id)->fetch();

if ($row['premod'] || $adm) {
    // Счетчик прочтений
    if (! isset($_SESSION['lib']) || (isset($_SESSION['lib']) && $_SESSION['lib'] !== $id)) {
        $_SESSION['lib'] = $id;
        $db->exec('UPDATE `library_texts` SET  `count_views`=' . ($row['count_views'] ? ++$row['count_views'] : 1) . ' WHERE `id`=' . $id);
    }

    // Запрашиваем выбранную статью из базы
    $symbols = 7000;
    $count_pages = ceil($db->query("SELECT CHAR_LENGTH(`text`) FROM `library_texts` WHERE `id`= '" . $id . "' LIMIT 1")->fetchColumn() / $symbols);
    if ($count_pages) {
        // Чтоб всегда последнюю страницу считал правильно
        $page = (int) ($page >= $count_pages ? $count_pages : $page);
        $text = $db->query('SELECT SUBSTRING(`text`, ' . ($page === 1 ? 1 : ($page - 1) * $symbols) . ', ' . ($symbols + 100) . ") FROM `library_texts` WHERE `id`='" . $id . "'")->fetchColumn();
        $tmp = mb_substr($text, $symbols, 100);
    } else {
        Utils::redir404();
    }

    $nav = $count_pages > 1 ? '<div class="topmenu">' . $tools->displayPagination('?id=' . $id . '&amp;', $page === 1 ? 0 : ($page - 1) * 1, $count_pages, 1) . '</div>' : '';
    $catalog = $db->query('SELECT `id`, `name` FROM `library_cats` WHERE `id` = ' . $row['cat_id'] . ' LIMIT 1')->fetch();
    echo '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a>'
        . ' | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . $tools->checkout($catalog['name']) . '</a>'
        . ($page > 1 ? ' | ' . $tools->checkout($row['name']) : '') . '</div>';

    // Верхняя постраничная навигация
    if ($count_pages > 1) {
        echo '<div class="topmenu">' . $tools->displayPagination('?id=' . $id . '&amp;', $page === 1 ? 0 : ($page - 1) * 1, $count_pages, 1) . '</div>';
    }
    if ($page === 1) {
        echo '<div class="list2">';
        // Заголовок статьи
        echo '<h2>' . $tools->checkout($row['name']) . '</h2>';

        // Описание к статье
        $obj = new Hashtags($row['id']);
        $rate = new Rating($row['id']);
        $uploader = $row['uploader_id']
            ? '<a href="' . $config['homeurl'] . '/profile/?user=' . $row['uploader_id'] . '">' . $tools->checkout($row['uploader']) . '</a>'
            : $tools->checkout($row['uploader']);
        echo '<table class="desc">'
            // Тэги
            . ($obj->getAllStatTags()
                ? '<tr><td class="caption">' . _t('The Tags') . ':</td>'
                . '<td>' . $obj->getAllStatTags(1) . '</td></tr>'
                : '')
            // Кто добавил?
            . '<tr>'
            . '<td class="caption">' . _t('Who added') . ':</td>'
            . '<td>' . $uploader . ' (' . $tools->displayDate($row['time']) . ')</td>'
            . '</tr>'
            // Рейтинг
            . '<tr>'
            . '<td class="caption">' . _t('Rating') . ':</td>'
            . '<td>' . $rate->viewRate(1) . '</td>'
            . '</tr>'
            // Прочтений
            . '<tr>'
            . '<td class="caption">' . _t('Number of readings') . ':</td>'
            . '<td>' . $row['count_views'] . '</td>'
            . '</tr>'
            // Комментарии
            . '<tr>';
        if ($row['comments']) {
            echo '<td class="caption"><a href="?act=comments&amp;id=' . $row['id'] . '">' . _t('Comments') . '</a>:</td>'
                . '<td>' . $row['comm_count'] . '</td>';
        } else {
            echo '<td class="caption">' . _t('Comments') . ':</td>'
                . '<td>' . _t('Comments are closed') . '</td>';
        }
        echo '</tr></table>';

        // Метки авторов
        echo '</div>';
    }

    $text = $tools->checkout(
        mb_substr(
            $text,
            ($page === 1 ? 0 : min(Utils::position($text, PHP_EOL), Utils::position($text, ' '))),
            (
            ($count_pages === 1 || $page === $count_pages)
                ? $symbols
                : $symbols + min(Utils::position($tmp, PHP_EOL), Utils::position($tmp, ' ')) - ($page === 1 ? 0 : min(Utils::position($text, PHP_EOL), Utils::position($text, ' ')))
            )
        ),
        1,
        1
    );
    $text = $tools->smilies($text, $user->rights ? 1 : 0);

    echo '<div class="list2" style="padding: 8px">';

    if ($page === 1) {
        // Картинка статьи
        if (file_exists(UPLOAD_PATH . 'library/images/big/' . $id . '.png')) {
            $img_style = 'width: 50%; max-width: 240px; height: auto; float: left; clear: both; margin: 10px';
            echo '<a href="../upload/library/images/orig/' . $id . '.png">'
                . '<img style="' . $img_style . '" src="../upload/library/images/big/' . $id . '.png" alt="screen" /></a>';
        }
    }

    // Выводим текст статьи
    echo $text .
        '<div style="clear: both"></div>' .
        '</div>';

    echo '<div class="phdr">' . _t('Download file') . ' <a href="?act=download&amp;type=txt&amp;id=' . $id . '">txt</a>'
        . ' | <a href="?act=download&amp;type=fb2&amp;id=' . $id . '">fb2</a></div>';

    echo $nav . ($user->isValid() && $page === 1 ? $rate->printVote() : '');

    if ($adm || ($db->query('SELECT `uploader_id` FROM `library_texts` WHERE `id` = ' . $id)->fetchColumn() === $user->id && $user->isValid())) {
        echo '<p><a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
            . '<a href="?act=del&amp;type=article&amp;id=' . $id . '">' . _t('Delete') . '</a></p>';
    }
} else {
    Utils::redir404();
}
