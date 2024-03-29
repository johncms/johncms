<?php

/**
 * @var $user_name
 * @var $user_login
 * @var $link_to_confirm
 * @var $config
 */

?>
@extends('system::mail/layouts/default', ['email_title' => __('Confirm email change')])
@section('content')
    <?= __('Hello, %s!', $user_name) ?>
    <p>
        <?= __('You have started the process of changing the email address on the <a href="%s">%s</a> website', $config['homeurl'], $config['copyright']) ?>
    </p>
    <p>
        <?= __('To continue please click the confirm button') ?>
    </p>
    <p style="text-align: center;">
        <a class="button" href="<?= $link_to_confirm ?>"
           style="background-color:#66615b;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:60px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;">
            <?= __('Confirm') ?>
        </a>
    </p>
    <p>
        <?= __('If you have not registered on our site, please ignore this email.') ?>
    </p>
@endsection
