<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$mp = new mainpage();

// Блок информации
echo '<div class="phdr"><b>' . _t('Information') . '</b></div>';
echo $mp->news;
echo '<div class="menu"><a href="news/index.php">' . _t('News archive') . '</a> (' . $mp->newscount . ')</div>' .
    '<div class="menu"><a href="pages/faq.php">' . _t('Information, FAQ') . '</a></div>';

// Блок общения
echo '<div class="phdr"><b>' . _t('Communication') . '</b></div>';

// Ссылка на гостевую
if ($set['mod_guest'] || $rights >= 7) {
    echo '<div class="menu"><a href="guestbook/index.php">' . _t('Guestbook') . '</a> (' . counters::guestbook() . ')</div>';
}

// Ссылка на Форум
if ($set['mod_forum'] || $rights >= 7) {
    echo '<div class="menu"><a href="forum/">' . _t('Forum') . '</a> (' . counters::forum() . ')</div>';
}

// Блок полезного
echo '<div class="phdr"><b>' . _t('Useful') . '</b></div>';

// Ссылка на загрузки
if ($set['mod_down'] || $rights >= 7) {
    echo '<div class="menu"><a href="download/">' . _t('Downloads') . '</a> (' . counters::downloads() . ')</div>';
}

// Ссылка на библиотеку
if ($set['mod_lib'] || $rights >= 7) {
    echo '<div class="menu"><a href="library/">' . _t('Library') . '</a> (' . counters::library() . ')</div>';
}

// Ссылка на библиотеку
if ($set['mod_gal'] || $rights >= 7) {
    echo '<div class="menu"><a href="gallery/">' . _t('Gallery') . '</a> (' . counters::gallery() . ')</div>';
}

if ($user_id || $set['active']) {
    echo '<div class="phdr"><b>' . _t('Community') . '</b></div>' .
        '<div class="menu"><a href="users/index.php">' . _t('Users') . '</a> (' . counters::users() . ')</div>' .
        '<div class="menu"><a href="users/album.php">' . _t('Photo Albums') . '</a> (' . counters::album() . ')</div>';
}

echo '<div class="phdr"><a href="http://gazenwagen.com">Gazenwagen</a></div>';
