<?php

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();
$config = $container->get('config')['johncms'];

/** @var PDO $db */
$db = $container->get(PDO::class);

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('News on the mainpage') . '</div>';

// Настройки Новостей
if (empty($config['news']) || isset($_GET['reset'])) {
    // Задаем настройки по умолчанию
    $settings = [
        'view'     => '1',
        'size'     => '200',
        'quantity' => '3',
        'days'     => '7',
        'breaks'   => '1',
        'smileys'  => '0',
        'tags'     => '1',
        'kom'      => '1',
    ];
    @$db->exec("DELETE FROM `cms_settings` WHERE `key` = 'news'");
    $db->exec("INSERT INTO `cms_settings` SET
        `key` = 'news',
        `val` = " . $db->quote(serialize($settings)) . "
    ");
    echo '<div class="rmenu"><p>' . _t('Default settings are set') . '</p></div>';
} elseif (isset($_POST['submit'])) {
    // Принимаем настройки из формы
    $settings['view'] = isset($_POST['view']) && $_POST['view'] >= 0 && $_POST['view'] < 4 ? intval($_POST['view']) : 1;
    $settings['size'] = isset($_POST['size']) && $_POST['size'] > 50 && $_POST['size'] < 500 ? intval($_POST['size']) : 200;
    $settings['quantity'] = isset($_POST['quantity']) && $_POST['quantity'] > 0 && $_POST['quantity'] < 16 ? intval($_POST['quantity']) : 3;
    $settings['days'] = isset($_POST['days']) && $_POST['days'] > 0 && $_POST['days'] < 16 ? intval($_POST['days']) : 7;
    $settings['breaks'] = isset($_POST['breaks']);
    $settings['smileys'] = isset($_POST['smileys']);
    $settings['tags'] = isset($_POST['tags']);
    $settings['kom'] = isset($_POST['kom']);
    $db->exec("UPDATE `cms_settings` SET
        `val` = " . $db->quote(serialize($settings)) . "
        WHERE `key` = 'news'
    ");
    echo '<div class="gmenu"><p>' . _t('Settings are saved successfully') . '</p></div>';
} else {
    // Получаем сохраненные настройки
    $settings = unserialize($config['news']);
}

// Форма ввода настроек
echo '<form action="index.php?act=news" method="post"><div class="menu"><p>' .
    '<h3>' . _t('Appearance') . '</h3>' .
    '<input type="radio" value="1" name="view" ' . ($settings['view'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Title + Text') . '<br>' .
    '<input type="radio" value="2" name="view" ' . ($settings['view'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Title') . '<br>' .
    '<input type="radio" value="3" name="view" ' . ($settings['view'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . _t('Text') . '<br>' .
    '<input type="radio" value="0" name="view" ' . (!$settings['view'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Not to show') . '</p>' .
    '<p><input name="breaks" type="checkbox" value="1" ' . ($settings['breaks'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Line breaks') . '<br>' .
    '<input name="smileys" type="checkbox" value="1" ' . ($settings['smileys'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Smilies') . '<br>' .
    '<input name="tags" type="checkbox" value="1" ' . ($settings['tags'] ? 'checked="checked"' : '') . ' />&#160;' . _t('bbCode Tags') . '<br>' .
    '<input name="kom" type="checkbox" value="1" ' . ($settings['kom'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Comments') . '</p>' .
    '<p><h3>' . _t('Text size') . '</h3>&#160;' .
    '<input type="text" size="3" maxlength="3" name="size" value="' . $settings['size'] . '" />&#160;(50 - 500)</p>' .
    '<p><h3>' . _t('Quantity of news') . '</h3>&#160;<input type="text" size="3" maxlength="2" name="quantity" value="' . $settings['quantity'] . '" />&#160;(1 - 15)</p>' .
    '<p><h3>' . _t('How many days to show?') . '</h3>&#160;<input type="text" size="3" maxlength="2" name="days" value="' . $settings['days'] . '" />&#160;(0 - 15)</p>' .
    '<br><p><input type="submit" value="' . _t('Save') . '" name="submit" /></p></div>' .
    '<div class="phdr"><a href="index.php?act=news&amp;reset">' . _t('Reset Settings') . '</a>' .
    '</div></form>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
