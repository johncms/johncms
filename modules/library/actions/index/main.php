<?php

echo '<div class="phdr"><strong>' . _t('Library') . '</strong></div>';
echo '<div class="topmenu"><a href="?act=search">' . _t('Search') . '</a> | <a href="?act=tagcloud">' . _t('Tag Cloud') . '</a></div>';
echo '<div class="gmenu"><p>';

if ($adm) {
    // Считаем число статей, ожидающих модерацию
    $res = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn();

    if ($res > 0) {
        echo '<div>' . _t('On moderation') . ': <a href="?act=premod">' . $res . '</a></div>';
    }
}

$res = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `time` > "' . (time() - 259200) . '" AND `premod` = 1')->fetchColumn();

if ($res) {
    echo '<img src="' . $assets->url('images/old/add.gif') . '" alt="" class="icon"><a href="?act=new">' . _t('New Articles') . '</a> (' . $res . ')<br>';
}

echo '<img src="' . $assets->url('images/old/rate.gif') . '" alt="" class="icon"><a href="?act=top">' . _t('Rating articles') . '</a><br>' .
    '<img src="' . $assets->url('images/old/talk.gif') . '" alt="" class="icon"><a href="?act=lastcom">' . _t('Latest comments') . '</a>' .
    '</p></div>';

$total = $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent`=0')->fetchColumn();
$y = 0;

if ($total) {
    $req = $db->query('SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE `parent`=0 ORDER BY `pos` ASC');

    while ($row = $req->fetch()) {
        $y++;
        echo '<div class="list' . ((++$i % 2) ? 2 : 1) . '">'
            . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a> ('
            . $db->query('SELECT COUNT(*) FROM `' . ($row['dir'] ? 'library_cats' : 'library_texts') . '` WHERE ' . ($row['dir'] ? '`parent`=' . $row['id'] : '`cat_id`=' . $row['id']))->fetchColumn() . ')';

        if (! empty($row['description'])) {
            echo '<div style="font-size: x-small; padding-top: 2px"><span class="gray">' . $tools->checkout($row['description']) . '</span></div>';
        }

        if ($adm) {
            echo '<div class="sub">'
                . ($y !== 1 ? '<a href="?act=move&amp;moveset=up&amp;posid=' . $y . '">' . _t('Up') . '</a> | ' : _t('Up') . ' | ')
                . ($y !== $total ? '<a href="?act=move&amp;moveset=down&amp;posid=' . $y . '">' . _t('Down') . '</a>' : _t('Down'))
                . ' | <a href="?act=moder&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Edit') . '</a>'
                . ' | <a href="?act=del&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a></div>';
        }

        echo '</div>';
    }
} else {
    echo '<div class="menu">' . _t('The list is empty') . '</div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($adm) {
    echo '<p><a href="?act=mkdir&amp;id=0">' . _t('Create Section') . '</a></p>';
}
