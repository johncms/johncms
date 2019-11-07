<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ConfigInterface;
use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var ConfigInterface    $config
 * @var ContainerInterface $container
 * @var ToolsInterface     $tools
 * @var UserInterface      $user
 * @var Engine             $view
 */

$container = App::getContainer();
$config = $container->get(ConfigInterface::class);
$tools = $container->get(ToolsInterface::class);
$user = $container->get(UserInterface::class);
$view = $container->get(Engine::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('reg', __DIR__ . '/templates/');

// Регистрируем папку с языками модуля
$container->get(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Если регистрация закрыта, выводим предупреждение
if (! $config->mod_reg || $user->isValid()) {
    echo $view->render('system::app/old_content', [
        'title'   => _t('Registration'),
        'content' => '<div class="menu padding">' . _t('Registration is temporarily closed') . '</div>',
    ]);
    exit;
}

$captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : null;
$reg_nick = isset($_POST['nick']) ? trim($_POST['nick']) : '';
$lat_nick = $tools->rusLat($reg_nick);
$reg_pass = isset($_POST['password']) ? trim($_POST['password']) : '';
$reg_name = isset($_POST['imname']) ? trim($_POST['imname']) : '';
$reg_about = isset($_POST['about']) ? trim($_POST['about']) : '';
$reg_sex = isset($_POST['sex']) ? trim($_POST['sex']) : '';

if (isset($_POST['submit'])) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    // Принимаем переменные
    $error = [];

    // Проверка Логина
    if (empty($reg_nick)) {
        $error['login'][] = _t('You have not entered Nickname');
    } elseif (mb_strlen($reg_nick) < 2 || mb_strlen($reg_nick) > 20) {
        $error['login'][] = _t('Nickname wrong length');
    }

    if (preg_match('/[^\da-z\-\@\*\(\)\?\!\~\_\=\[\]]+/', $lat_nick)) {
        $error['login'][] = _t('Invalid characters');
    }

    // Проверка пароля
    if (empty($reg_pass)) {
        $error['password'][] = _t('You have not entered password');
    } elseif (mb_strlen($reg_pass) < 3) {
        $error['password'][] = _t('Invalid length');
    }

    // Проверка пола
    if ($reg_sex != 'm' && $reg_sex != 'zh') {
        $error['sex'] = _t('You have not selected genger');
    }

    // Проверка кода CAPTCHA
    if (! $captcha
        || ! isset($_SESSION['code'])
        || mb_strlen($captcha) < 3
        || strtolower($captcha) != strtolower($_SESSION['code'])
    ) {
        $error['captcha'] = _t('The security code is not correct');
    }

    unset($_SESSION['code']);

    // Проверка переменных
    if (empty($error)) {
        $pass = md5(md5($reg_pass));
        $reg_name = htmlspecialchars(mb_substr($reg_name, 0, 50));
        $reg_about = htmlspecialchars(mb_substr($reg_about, 0, 1000));
        // Проверка, занят ли ник
        $stmt = $db->prepare('SELECT * FROM `users` WHERE `name_lat` = ?');
        $stmt->execute([$lat_nick]);

        if ($stmt->rowCount()) {
            $error['login'][] = _t('Selected Nickname is already in use');
        }
    }

    if (empty($error)) {
        /** @var Johncms\Api\EnvironmentInterface $env */
        $env = $container->get(Johncms\Api\EnvironmentInterface::class);

        $preg = $config->mod_reg > 1 ? 1 : 0;
        $db->prepare('
          INSERT INTO `users` SET
          `name` = ?,
          `name_lat` = ?,
          `password` = ?,
          `imname` = ?,
          `about` = ?,
          `sex` = ?,
          `rights` = 0,
          `ip` = ?,
          `ip_via_proxy` = ?,
          `browser` = ?,
          `datereg` = ?,
          `lastdate` = ?,
          `sestime` = ?,
          `preg` = ?,
          `set_user` = \'\',
          `set_forum` = \'\',
          `set_mail` = \'\',
          `smileys` = \'\'
        ')->execute([
            $reg_nick,
            $lat_nick,
            $pass,
            $reg_name,
            $reg_about,
            $reg_sex,
            $env->getIp(),
            $env->getIpViaProxy(),
            $env->getUserAgent(),
            time(),
            time(),
            time(),
            $preg,
        ]);

        $usid = $db->lastInsertId();

        echo '<div class="menu"><p><h3>' . _t('Your registratiton data') . '</h3>'
            . _t('Your ID') . ': <b>' . $usid . '</b><br>'
            . _t('Your Username') . ': <b>' . $reg_nick . '</b><br>'
            . _t('Your Password') . ': <b>' . $reg_pass . '</b></p>';

        if ($config->mod_reg == 1) {
            echo '<p><span class="red"><b>' . _t('Please, wait until a moderator approves your registration') . '</b></span></p>';
        } else {
            $_SESSION['uid'] = $usid;
            $_SESSION['ups'] = md5(md5($reg_pass));
            echo '<p><a href="' . $config->homeurl . '">' . _t('Enter') . '</a></p>';
        }

        echo '</div>';

        echo $view->render('system::app/old_content', [
            'title'   => _t('Registration'),
            'content' => ob_get_clean(),
        ]);
        exit;
    }
}

// Форма регистрации
if ($config->mod_reg == 1) {
    echo '<div class="rmenu"><p>' . _t('You can get authorized on the site after confirmation of your registration.') . '</p></div>';
}

$code = (string) new Batumibiz\Captcha\Code;
$_SESSION['code'] = $code;

echo $view->render('reg::index', [
    'error'     => $error,
    'reg_nick'  => $reg_nick,
    'reg_pass'  => $reg_pass,
    'reg_name'  => $reg_name,
    'reg_about' => $reg_about,
    'captcha'   => new Batumibiz\Captcha\Image($code),
]);
