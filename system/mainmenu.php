<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Johncms\Counters $counters */
$counters = App::getContainer()->get('counters');
$mp = new Johncms\NewsWidget();

// Блок информации
echo '<div class="phdr"><b>' . _t('Information', 'system') . '</b></div>';
echo $mp->news;
echo '<div class="menu"><a href="news/">' . _t('News archive', 'system') . '</a> (' . $mp->newscount . ')</div>' .
    '<div class="menu"><a href="help/">' . _t('Information, FAQ', 'system') . '</a></div>';

////////////////////////////////////////////////////////////
// Блок общения                                           //
////////////////////////////////////////////////////////////
echo '<div class="phdr"><b>' . _t('Communication', 'system') . '</b></div>';

// Ссылка на гостевую
if ($set['mod_guest'] || $rights >= 7) {
    echo '<div class="menu"><a href="guestbook/index.php">' . _t('Guestbook', 'system') . '</a> (' . $counters->guestbook() . ')</div>';
}

// Ссылка на Форум
if ($set['mod_forum'] || $rights >= 7) {
    echo '<div class="menu"><a href="forum/">' . _t('Forum', 'system') . '</a> (' . $counters->forum() . ')</div>';
}

////////////////////////////////////////////////////////////
// Блок полезного                                         //
////////////////////////////////////////////////////////////
echo '<div class="phdr"><b>' . _t('Useful', 'system') . '</b></div>';

// Ссылка на загрузки
if ($set['mod_down'] || $rights >= 7) {
    echo '<div class="menu"><a href="downloads/">' . _t('Downloads', 'system') . '</a> (' . $counters->downloads() . ')</div>';
}

// Ссылка на библиотеку
if ($set['mod_lib'] || $rights >= 7) {
    echo '<div class="menu"><a href="library/">' . _t('Library', 'system') . '</a> (' . counters::library() . ')</div>';
}

////////////////////////////////////////////////////////////
// Блок Сообщества                                        //
////////////////////////////////////////////////////////////
if ($user_id || $set['active']) {
    echo '<div class="phdr"><b>' . _t('Community', 'system') . '</b></div>' .
        '<div class="menu"><a href="users/index.php">' . _t('Users', 'system') . '</a> (' . counters::users() . ')</div>' .
        '<div class="menu"><a href="album/index.php">' . _t('Photo Albums', 'system') . '</a> (' . $counters->album() . ')</div>';
}

echo '<div class="phdr"><a href="http://gazenwagen.com">Gazenwagen</a></div>';
