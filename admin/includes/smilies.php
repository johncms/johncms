<?php

defined('_IN_JOHNADM') or die('Error: restricted access');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Tools $tools */
$tools = $container->get('tools');

$config = $container->get('config')['johncms'];

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Smilies') . '</div>';

$ext = ['gif', 'jpg', 'jpeg', 'png']; // Список разрешенных расширений
$smileys = [];

// Обрабатываем простые смайлы
foreach (glob(ROOT_PATH . 'images' . DIRECTORY_SEPARATOR . 'smileys' . DIRECTORY_SEPARATOR . 'simply' . DIRECTORY_SEPARATOR . '*') as $var) {
    $file = basename($var);
    $name = explode(".", $file);
    if (in_array($name[1], $ext)) {
        $smileys['usr'][':' . $name[0]] = '<img src="' . $config['homeurl'] . '/images/smileys/simply/' . $file . '" alt="" />';
    }
}

// Обрабатываем Админские смайлы
foreach (glob(ROOT_PATH . 'images' . DIRECTORY_SEPARATOR . 'smileys' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . '*') as $var) {
    $file = basename($var);
    $name = explode(".", $file);
    if (in_array($name[1], $ext)) {
        $smileys['adm'][':' . $tools->trans($name[0]) . ':'] = '<img src="' . $config['homeurl'] . '/images/smileys/admin/' . $file . '" alt="" />';
        $smileys['adm'][':' . $name[0] . ':'] = '<img src="' . $config['homeurl'] . '/images/smileys/admin/' . $file . '" alt="" />';
    }
}

// Обрабатываем смайлы каталога
foreach (glob(ROOT_PATH . 'images' . DIRECTORY_SEPARATOR . 'smileys' . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*') as $var) {
    $file = basename($var);
    $name = explode(".", $file);
    if (in_array($name[1], $ext)) {
        $path = $config['homeurl'] . '/images/smileys/user/' . basename(dirname($var));
        $smileys['usr'][':' . $tools->trans($name[0]) . ':'] = '<img src="' . $path . '/' . $file . '" alt="" />';
        $smileys['usr'][':' . $name[0] . ':'] = '<img src="' . $path . '/' . $file . '" alt="" />';
    }
}

// Записываем в файл Кэша
if (file_put_contents(ROOT_PATH . 'files/cache/smileys.dat', serialize($smileys))) {
    echo '<div class="gmenu"><p>' . _t('Smilie cache updated successfully') . '</p></div>';
} else {
    echo '<div class="rmenu"><p>' . _t('Error updating cache') . '</p></div>';
}
$total = count($smileys['adm']) + count($smileys['usr']);
echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
