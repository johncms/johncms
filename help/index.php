<?php

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');

$lng_faq = core::load_lng('faq');
$textl = 'FAQ';
$headmod = 'faq';
require('../incfiles/head.php');

// Обрабатываем ссылку для возврата
if (empty($_SESSION['ref'])) {
    $_SESSION['ref'] = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $home;
}

// Сколько смайлов разрешено выбрать пользователям?
$user_smileys = 20;

// Названия директорий со смайлами
function smiliesCat()
{
    return [
        'animals'       => _td('Animals', 'help'),
        'brawl_weapons' => _td('Brawl, Weapons', 'help'),
        'emotions'      => _td('Emotions', 'help'),
        'flowers'       => _td('Flowers', 'help'),
        'food_alcohol'  => _td('Food, Alcohol', 'help'),
        'gestures'      => _td('Gestures', 'help'),
        'holidays'      => _td('Holidays', 'help'),
        'love'          => _td('Love', 'help'),
        'misc'          => _td('Miscellaneous', 'help'),
        'music'         => _td('Music, Dancing', 'help'),
        'sports'        => _td('Sports', 'help'),
        'technology'    => _td('Technology', 'help'),
    ];
}

// Выбор действия
$array = [
    'admsmilies',
    'avatars',
    'forum',
    'my_smilies',
    'set_my_sm',
    'smilies',
    'tags',
    'usersmilies',
];

if ($act && ($key = array_search($act, $array)) !== false && file_exists('includes/' . $array[$key] . '.php')) {
    require('includes/' . $array[$key] . '.php');
} else {
    // Главное меню FAQ
    echo '<div class="phdr"><b>' . _t('Information, FAQ') . '</b></div>' .
        '<div class="menu"><a href="?act=forum">' . _td('Forum rules', 'help') . '</a></div>' .
        '<div class="menu"><a href="?act=tags">' . _td('bbCode Tags', 'help') . '</a></div>' .
        '<div class="menu"><a href="?act=avatars">' . _t('Avatars') . '</a></div>' .
        '<div class="menu"><a href="?act=smilies">' . _t('Smilies') . '</a></div>' .
        '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
}

require('../incfiles/end.php');
