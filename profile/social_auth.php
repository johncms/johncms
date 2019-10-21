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



// TODO: Рефакторинг
$config = $container->get('config')['social'];


$soc_config = [
    'callback'  => 'https://johncms.com/profile/social_auth.php',
    'providers' => [
        'Vkontakte' => [
            'callback' => 'https://johncms.com/social_auth.php?service=vk',
            'enabled' => ($config['vk']['active'] === 'on'),
            'keys'    => [
                'id'     => $config['vk']['app_id'],
                'secret' => $config['vk']['app_secret'],
            ]
        ],
    ]
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

                $db->prepare("INSERT INTO social_users SET 
                user_id = ?, 
                login = ?
                name = ?,
                last_name = ?,
                service = 'vk',
                ext_id = ?,
                oatoken = ?,
                oatoken_expires = ?,
                refresh_token = ?,
                gender = ?,
                birth = ?,
                profile_url = ?
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








require('../system/end.php');

