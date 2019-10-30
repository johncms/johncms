<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

$headmod = 'login';
ob_start();

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var League\Plates\Engine $view */
$view = $container->get(League\Plates\Engine::class);

echo '<div class="phdr"><b>' . _t('Login', 'system') . '</b></div>';
$error = [];
$captcha = false;
$display_form = 1;
$user_login = $_POST['n'] ?? '';
$user_pass = $_POST['p'] ?? '';
$user_mem = isset($_POST['mem']) ? 1 : 0;
$user_code = isset($_POST['code']) ? trim($_POST['code']) : null;

if ($user_pass && ! $user_login) {
    $error[] = _t('You have not entered login', 'system');
}

if ($user_login && ! $user_pass) {
    $error[] = _t('You have not entered password', 'system');
}

if ($user_login && (mb_strlen($user_login) < 2 || mb_strlen($user_login) > 20)) {
    $error[] = _t('Nickname', 'system') . ': ' . _t('Invalid length', 'system');
}

if ($user_pass && (mb_strlen($user_pass) < 1)) {
    $error[] = _t('Password', 'system') . ': ' . _t('Invalid length', 'system');
}

if (! $error && $user_pass && $user_login) {
    // Запрос в базу на юзера
    $stmt = $db->prepare('SELECT * FROM `users` WHERE `name_lat` = ? LIMIT 1');
    $stmt->execute([$tools->rusLat($user_login)]);

    if ($stmt->rowCount()) {
        $systemUser = $stmt->fetch();

        if ($systemUser['failed_login'] > 2) {
            if ($user_code) {
                if (mb_strlen($user_code) > 2 && strtolower($user_code) === strtolower($_SESSION['code'])) {
                    // Если введен правильный проверочный код
                    $captcha = true;
                } else {
                    // Если проверочный код указан неверно
                    $error[] = _t('The security code is not correct', 'system');
                }

                unset($_SESSION['code']);
            } else {
                // Показываем CAPTCHA
                $display_form = 0;

                $cap = new Batumibiz\Captcha\Captcha;
                $code = $cap->generateCode();
                $_SESSION['code'] = $code;

                echo '<form' . ($id ? 'action="?id=' . $id . '" ' : '') . ' method="post">' .
                    '<div class="menu"><p>' .
                    '<img alt="' . _t('Verification code') . '" width="' . $cap->width . '" height="' . $cap->height . '" src="' . $cap->generateImage($code) . '"/><br>' .
                    _t('Enter verification code',
                        'system') . ':<br><input type="text" size="5" maxlength="5"  name="code"/>' .
                    '<input type="hidden" name="n" value="' . htmlspecialchars($user_login) . '"/>' .
                    '<input type="hidden" name="p" value="' . $user_pass . '"/>' .
                    '<input type="hidden" name="mem" value="' . $user_mem . '"/>' .
                    '<input type="submit" name="submit" value="' . _t('Continue', 'system') . '"/></p></div></form>';
            }
        }

        if ($systemUser['failed_login'] < 3 || $captcha) {
            if (md5(md5($user_pass)) == $systemUser['password']) {
                // Если логин удачный
                $display_form = 0;
                $db->exec("UPDATE `users` SET `failed_login` = '0' WHERE `id` = " . $systemUser['id']);

                if (! $systemUser['preg']) {
                    // Если регистрация не подтверждена
                    echo '<div class="rmenu"><p>' . _t('Sorry, but your request for registration is not considered yet. Please, be patient.',
                            'system') . '</p></div>';
                } else {
                    // Если все проверки прошли удачно, подготавливаем вход на сайт
                    if (isset($_POST['mem'])) {
                        // Установка данных COOKIE
                        $cuid = (string) $systemUser['id'];
                        $cups = md5($user_pass);
                        setcookie('cuid', $cuid, time() + 3600 * 24 * 365, '/');
                        setcookie('cups', $cups, time() + 3600 * 24 * 365, '/');
                    }

                    // Установка данных сессии
                    $_SESSION['uid'] = $systemUser['id'];
                    $_SESSION['ups'] = md5(md5($user_pass));

                    $db->exec("UPDATE `users` SET `sestime` = '" . time() . "' WHERE `id` = " . $systemUser['id']);
                    $set_user = unserialize($systemUser['set_user']);
                    header('Location: ' . $config->homeurl);

                    echo '<div class="gmenu"><p><b><a href="' . $config->homeurl . '">' . _t('Enter site',
                            'system') . '</a></b></p></div>';
                }
            } else {
                // Если логин неудачный
                if ($systemUser['failed_login'] < 3) {
                    // Прибавляем к счетчику неудачных логинов
                    $db->exec("UPDATE `users` SET `failed_login` = '" . ($systemUser['failed_login'] + 1) . "' WHERE `id` = " . $systemUser['id']);
                }

                $error[] = _t('Authorization failed', 'system');
            }
        }
    } else {
        $error[] = _t('Authorization failed', 'system');
    }
}

if ($display_form) {
    if ($error) {
        echo $tools->displayError($error);
    }

    echo '<div class="gmenu"><form method="post"><p>' . _t('Username', 'system') . ':<br>' .
        '<input type="text" name="n" value="' . htmlentities($user_login, ENT_QUOTES, 'UTF-8') . '" maxlength="20"/>' .
        '<br>' . _t('Password', 'system') . ':<br>' .
        '<input type="password" name="p" maxlength="20"/></p>' .
        '<p><input type="checkbox" name="mem" value="1" checked="checked"/>' . _t('Remember', 'system') . '</p>' .
        '<p><input type="submit" value="' . _t('Login', 'system') . '"/></p>' .
        '</form></div>' .
        '<div class="menu"><p>' . $tools->image('user.png') . '<a href="registration/">' . _t('Registration',
            'system') . '</a></p></div>' .
        '<div class="bmenu"><p>' . $tools->image('lock.png') . '<a href="profile/skl.php?continue">' . _t('Forgot password?',
            'system') . '</a></p></div>';
}

echo $view->render('system::app/old_content', [
    'title'   => _t('Guestbook'),
    'content' => ob_get_clean(),
]);
