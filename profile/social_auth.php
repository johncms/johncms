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

define('_IN_JOHNCMS', 1);

require('../system/bootstrap.php');

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);


require('../system/head.php');



// TODO: Отрефакторить
$config = $container->get('config')['social'];

echo '<div class="phdr"><b>' . _t('Привязка профиля') . '</b></div>';

$soc_config = [
    'callback' => 'https://johncms.com/profile/social_auth.php',
    'providers' => [
        'Vkontakte' => [
            'callback' => 'https://johncms.com/profile/social_auth.php?service=vk',
            'enabled' => ($config['vk']['active'] === 'on'),
            'keys' => [
                'id' => $config['vk']['app_id'],
                'secret' => $config['vk']['app_secret'],
            ],
        ],
        'Google' => [
            'callback' => 'https://johncms.com/profile/social_auth.php?service=google',
            'enabled' => ($config['google']['active'] === 'on'),
            'keys' => [
                'id' => $config['google']['app_id'],
                'secret' => $config['google']['app_secret'],
            ],
            'authorize_url_parameters' => [
                'approval_prompt' => 'force',
                'access_type' => 'offline',
                //And so on.
            ],
        ],
        'Twitter' => [
            'callback' => 'https://johncms.com/profile/social_auth.php?service=twitter',
            'enabled' => ($config['twitter']['active'] === 'on'),
            'keys' => [
                'id' => $config['twitter']['app_id'],
                'secret' => $config['twitter']['app_secret'],
            ],
        ],
    ],
];

$service = $_REQUEST['service'] ?? '';


try {
    $hybridauth = new \Hybridauth\Hybridauth($soc_config);

    switch ($service) {

        case 'vk':
            $adapter = $hybridauth->authenticate('Vkontakte');

            if($adapter->isConnected()) {
                $tokens = $adapter->getAccessToken();
                $userProfile = $adapter->getUserProfile();

                $check = $db->query("SELECT * FROM social_users WHERE service = 'vk' AND ext_id = ". $db->quote($userProfile->identifier))->fetch();

                if(empty($check)) {
                    $db->prepare("INSERT INTO social_users SET 
                        `user_id` = ?, 
                        `login` = ?,
                        `name` = ?,
                        `last_name` = ?,
                        `service` = 'vk',
                        `ext_id` = ?,
                        `oatoken` = ?,
                        `oatoken_expires` = ?,
                        `refresh_token` = ?,
                        `gender` = ?,
                        `birth` = ?,
                        `profile_url` = ?
                    ")->execute([
                        $systemUser->id,
                        $userProfile->displayName,
                        $userProfile->firstName,
                        $userProfile->lastName,
                        $userProfile->identifier,
                        $tokens['access_token'],
                        $tokens['expires_at'],
                        $tokens['refresh_token'],
                        $userProfile->gender,
                        $userProfile->birthDay,
                        $userProfile->profileURL,
                    ]);
                    echo '<div class="gmenu"><p>'._t('Success. You are linked your social profile to current site').'</p></div>';
                } else {
                    if ($check['user_id'] != $systemUser->id) {
                        echo '<div class="rmenu"><p>'._t('Error. Social site user already linked to other site user.').'</p></div>';
                    } else {
                        echo '<div class="gmenu"><p>'._t('Social site user already linked to your profile').'</p></div>';
                    }
                }

            }

            break;

        case 'google':
            $adapter = $hybridauth->authenticate('Google');

            if($adapter->isConnected()) {
                $tokens = $adapter->getAccessToken();
                $userProfile = $adapter->getUserProfile();

                $check = $db->query("SELECT * FROM social_users WHERE service = 'google' AND ext_id = ". $db->quote($userProfile->identifier))->fetch();

                if(empty($check)) {
                    $db->prepare("INSERT INTO social_users SET 
                        `user_id` = ?, 
                        `login` = ?,
                        `name` = ?,
                        `last_name` = ?,
                        `service` = 'google',
                        `ext_id` = ?,
                        `oatoken` = ?,
                        `oatoken_expires` = ?,
                        `refresh_token` = ?,
                        `gender` = ?,
                        `birth` = ?,
                        `profile_url` = ?
                    ")->execute([
                        $systemUser->id,
                        $userProfile->displayName,
                        $userProfile->firstName,
                        $userProfile->lastName,
                        $userProfile->identifier,
                        $tokens['access_token'],
                        $tokens['expires_at'],
                        $tokens['refresh_token'],
                        $userProfile->gender,
                        $userProfile->birthDay,
                        $userProfile->profileURL,
                    ]);
                    echo '<div class="gmenu"><p>'._t('Success. You are linked your social profile to current site').'</p></div>';
                } else {
                    if ($check['user_id'] != $systemUser->id) {
                        echo '<div class="rmenu"><p>'._t('Error. Social site user already linked to other site user.').'</p></div>';
                    } else {
                        echo '<div class="gmenu"><p>'._t('Social site user already linked to your profile').'</p></div>';
                    }
                }

            }

            break;

        default:
            echo _t('Wrong data');

    }

    $adapter->disconnect();
}
catch (\Exception $e) {
    echo $e->getMessage();
}


?>
    <div class="phdr"><a href="/profile/?act=office"><?= _t('Back') ?></a></div>
<?php


require('../system/end.php');

