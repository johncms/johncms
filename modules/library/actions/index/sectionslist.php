<?php

$total = $db->query(
    'SELECT COUNT(*) FROM `library_cats` WHERE '
    . ($id !== null ? '`parent`=' . $id : '`parent`=0')
)->fetchColumn();
$nav = ($total > $user->config->kmess) ? '<div class="topmenu">' . $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>' : '';
$y = 0;

if ($total) {
    $sql = $db->query(
        'SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE '
        . ($id !== null ? '`parent`=' . $id : '`parent`=0') . ' ORDER BY `pos` ASC LIMIT ' . $start . ',' . $user->config->kmess
    );
    echo $nav;

    function libCounter(int $id, int $dir): string
    {
        $db = di(PDO::class);
        return $db->query('SELECT COUNT(*) FROM `' . ($dir ? 'library_cats' : 'library_texts') . '` WHERE '
                . ($dir ? '`parent` = ' . $id : '`cat_id` = ' . $id))->fetchColumn()
            . ' ' . ($dir ? ' ' . _t('Sections') : ' ' . _t('Articles')) . ')';
    }

    while ($row = $sql->fetch()) {
        $y++;
        echo '<div class="list' . ((++$i % 2) ? 2 : 1) . '">'
            . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $tools->checkout($row['name']) . '</a> (' . libCounter($row['id'], $row['dir']) . ')'
            . '<div class="sub"><span class="gray">' . $tools->checkout($row['description']) . '</span></div>';

        if ($adm) {
            echo '<div class="sub">'
                . ($y !== 1 ? '<a href="?do=dir&amp;id=' . $id . '&amp;act=move&amp;moveset=up&amp;posid=' . $y . '">' . _t('Up')
                    . '</a> | ' : '' . _t('Up') . ' | ')
                . ($y !== $total
                    ? '<a href="?do=dir&amp;id=' . $id . '&amp;act=move&amp;moveset=down&amp;posid=' . $y . '">' . _t('Down') . '</a>'
                    : _t('Down'))
                . ' | <a href="?act=moder&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Edit') . '</a>'
                . ' | <a href="?act=del&amp;type=dir&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a></div>';
        }

        echo '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
echo $nav;

if ($adm) {
    echo '<p><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . _t('Edit') . '</a><br>'
        . '<a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . _t('Delete') . '</a><br>'
        . '<a href="?act=mkdir&amp;id=' . $id . '">' . _t('Create') . '</a></p>';
}
