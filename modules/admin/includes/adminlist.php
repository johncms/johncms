<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\UserProperties;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 */

$title = __('Administration');
$nav_chain->add($title);
$data = [];

$sw = 0;
$adm = 0;
$smd = 0;
$mod = 0;

$items = [];

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '9'");
if ($req->rowCount()) {
    $block = [
        'name'  => __('Supervisors'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_id'] = $res['id'];
        $user_properties = new UserProperties();
        $user_data = $user_properties->getFromArray($res);
        $res = array_merge($res, $user_data);
        $block['items'][] = $res;
        $sw++;
    }

    $items[] = $block;
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '7' ORDER BY `name` ASC");

if ($req->rowCount()) {
    $block = [
        'name'  => __('Administrators'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_id'] = $res['id'];
        $user_properties = new UserProperties();
        $user_data = $user_properties->getFromArray($res);
        $res = array_merge($res, $user_data);
        $block['items'][] = $res;
        $adm++;
    }

    $items[] = $block;
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` = '6' ORDER BY `name` ASC");

if ($req->rowCount()) {
    $block = [
        'name'  => __('Super Moderators'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_id'] = $res['id'];
        $user_properties = new UserProperties();
        $user_data = $user_properties->getFromArray($res);
        $res = array_merge($res, $user_data);
        $block['items'][] = $res;
        ++$smd;
    }

    $items[] = $block;
}

$req = $db->query("SELECT * FROM `users` WHERE `rights` BETWEEN '1' AND '5' ORDER BY `name` ASC");

if ($req->rowCount()) {
    $block = [
        'name'  => __('Moderators'),
        'items' => [],
    ];

    while ($res = $req->fetch()) {
        $res['user_id'] = $res['id'];
        $user_properties = new UserProperties();
        $user_data = $user_properties->getFromArray($res);
        $res = array_merge($res, $user_data);
        $block['items'][] = $res;
        ++$mod;
    }

    $items[] = $block;
}

$data['total'] = ($sw + $adm + $smd + $mod);
$data['items'] = $items ?? [];
$data['back_url'] = '/admin/';

echo $view->render(
    'admin::admin_list',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
