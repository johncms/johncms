<?php

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');

/** @var Zend\I18n\Translator\Translator $translator */
$translator = App::getContainer()->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

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
        'animals'       => _t('Animals'),
        'brawl_weapons' => _t('Brawl, Weapons'),
        'emotions'      => _t('Emotions'),
        'flowers'       => _t('Flowers'),
        'food_alcohol'  => _t('Food, Alcohol'),
        'gestures'      => _t('Gestures'),
        'holidays'      => _t('Holidays'),
        'love'          => _t('Love'),
        'misc'          => _t('Miscellaneous'),
        'music'         => _t('Music, Dancing'),
        'sports'        => _t('Sports'),
        'technology'    => _t('Technology'),
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
        '<div class="menu"><a href="?act=forum">' . _t('Forum rules') . '</a></div>' .
        '<div class="menu"><a href="?act=tags">' . _t('bbCode Tags') . '</a></div>' .
        '<div class="menu"><a href="?act=avatars">' . _t('Avatars') . '</a></div>' .
        '<div class="menu"><a href="?act=smilies">' . _t('Smilies') . '</a></div>' .
        '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
}

require('../incfiles/end.php');
