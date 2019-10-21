<?php
/**
 * JohnCMS Content Management System (https://johncms.com)
 *
 * For copyright and license information, please see the LICENSE
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        https://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNADM') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

$config = $container->get('config')['social'];

// Проверяем права доступа
if ($systemUser->rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Social website integration') . '</div>';


$current_settings = [];
$social_array = [
    'vk',
    'twitter',
    'github',
    'google',
];
foreach ($social_array as $item) {
    $config[$item]['active'] = isset($_POST['social'][$item]['active']) ? trim($_POST['social'][$item]['active']) : $config[$item]['active'] ?? '0';
    $config[$item]['app_id'] = isset($_POST['social'][$item]['app_id']) ? trim($_POST['social'][$item]['app_id']) : $config[$item]['app_id'] ?? '0';
    $config[$item]['app_secret'] = isset($_POST['social'][$item]['app_secret']) ? trim($_POST['social'][$item]['app_secret']) : $config[$item]['app_secret'] ?? '0';
}

if (isset($_POST['submit'])) {
    // Сохраняем настройки системы
    $social = $_REQUEST['social'] ?? [];
    $configFile = "<?php\n\n" . 'return ' . var_export(['social' => $config], true) . ";\n";

    if (!file_put_contents(ROOT_PATH . 'system/config/social.local.php', $configFile)) {
        echo 'ERROR: Can not write social.local.php</body></html>';
        exit;
    }

    echo '<div class="rmenu">' . _t('Settings are saved successfully') . '</div>';

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

// Форма ввода параметров системы
echo '<form action="index.php?act=social_setting" method="post"><div class="menu">';

?>
    <!-- VK -->
    <h3><?= _t('Settings for VK') ?></h3>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="social[vk][active]" id="vk_active"
            <?= !empty($config['vk']['active']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="vk_active">
            <?= _t('Active') ?>
        </label>
    </div>
    <div class="form-group">
        <label for="vk_app_id"><?= _t('Application ID') ?></label>
        <input type="text" class="form-control" id="vk_app_id"
               name="social[vk][app_id]"
               value="<?= $config['vk']['app_id'] ?>"
               placeholder="<?= _t('Application ID') ?>">
    </div>
    <div class="form-group">
        <label for="vk_app_secret"><?= _t('Protected Key') ?></label>
        <input type="text" class="form-control" id="vk_app_secret"
               name="social[vk][app_secret]"
               value="<?= $config['vk']['app_secret'] ?>"
               placeholder="<?= _t('Protected Key') ?>">
    </div>

    <!-- Twitter -->
    <h3 class="mt-3"><?= _t('Settings for Twitter') ?></h3>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="social[twitter][active]" id="twitter_active"
            <?= !empty($config['twitter][']['active']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="twitter_active">
            <?= _t('Active') ?>
        </label>
    </div>
    <div class="form-group">
        <label for="twitter_app_id"><?= _t('Consumer Key:') ?></label>
        <input type="text" class="form-control" id="twitter_app_id"
               name="social[twitter][app_id]"
               value="<?= $config['twitter']['app_id'] ?>"
               placeholder="<?= _t('Consumer Key:') ?>">
    </div>
    <div class="form-group">
        <label for="twitter_app_secret"><?= _t('Application Secret Code (Consumer secret):') ?></label>
        <input type="text" class="form-control" id="twitter_app_secret"
               name="social[twitter][app_secret]"
               value="<?= $config['twitter']['app_secret'] ?>"
               placeholder="<?= _t('Consumer secret') ?>">
    </div>

    <!-- GitHub -->
    <h3 class="mt-3"><?= _t('Settings for GitHub') ?></h3>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="social[github][active]" id="github_active"
            <?= !empty($config['github']['active']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="github_active">
            <?= _t('Active') ?>
        </label>
    </div>
    <div class="form-group">
        <label for="github_app_id"><?= _t('Application ID') ?></label>
        <input type="text" class="form-control" id="github_app_id"
               name="social[github][app_id]"
               value="<?= $config['github']['app_id'] ?>"
               placeholder="<?= _t('Application ID') ?>">
    </div>
    <div class="form-group">
        <label for="github_app_secret"><?= _t('Secret Code') ?></label>
        <input type="text" class="form-control" id="github_app_secret"
               name="social[github][app_secret]"
               value="<?= $config['github']['app_secret'] ?>"
               placeholder="<?= _t('Secret Code') ?>">
    </div>

    <!-- Google -->
    <h3 class="mt-3"><?= _t('Settings for Google') ?></h3>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="social[google][active]" id="google_active"
            <?= !empty($config['github']['active']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="google_active">
            <?= _t('Active') ?>
        </label>
    </div>
    <div class="form-group">
        <label for="google_app_id"><?= _t('Client ID') ?></label>
        <input type="text" class="form-control" id="google_app_id"
               name="social[google][app_id]"
               value="<?= $config['google']['app_id'] ?>"
               placeholder="<?= _t('Client ID') ?>">
    </div>
    <div class="form-group">
        <label for="google_app_secret"><?= _t('Secret Code (Client secret)') ?></label>
        <input type="text" class="form-control" id="google_app_secret"
               name="social[google][app_secret]"
               value="<?= $config['google']['app_id'] ?>"
               placeholder="<?= _t('Secret Code') ?>">
    </div>
<?php
echo '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
    '<div class="phdr">&#160;</div>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
