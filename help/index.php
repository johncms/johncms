<?php

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');

$lng_faq = core::load_lng('faq');
$textl = 'FAQ';
$headmod = 'faq';
require('../incfiles/head.php');

// Задаем домен для перевода
_setDomain('help');

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
        'animals'       => _td('Animals'),
        'brawl_weapons' => _td('Brawl, Weapons'),
        'emotions'      => _td('Emotions'),
        'flowers'       => _td('Flowers'),
        'food_alcohol'  => _td('Food, Alcohol'),
        'gestures'      => _td('Gestures'),
        'holidays'      => _td('Holidays'),
        'love'          => _td('Love'),
        'misc'          => _td('Miscellaneous'),
        'music'         => _td('Music, Dancing'),
        'sports'        => _td('Sports'),
        'technology'    => _td('Technology'),
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
        '<div class="menu"><a href="?act=forum">' . _td('Forum rules') . '</a></div>' .
        '<div class="menu"><a href="?act=tags">' . _td('bbCode Tags') . '</a></div>' .
        '<div class="menu"><a href="?act=avatars">' . _t('Avatars') . '</a></div>' .
        '<div class="menu"><a href="?act=smilies">' . _t('Smilies') . '</a></div>' .
        '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
}

require('../incfiles/end.php');
