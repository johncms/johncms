<?php

/**
 * @var $user_name
 * @var $user_login
 * @var $link_to_confirm
 * @var $config
 */

?>
@extends('system::mail/layouts/default', ['email_title' => __('Registration on the website')])
@section('content')
    <?= __('Hello, %s!', $user_name) ?>
    <p>
        <?= __('You have successfully registered on the <a href="%s">%s</a> website', $config['homeurl'], $config['copyright']) ?>
    </p>
    <p>
        <?= __('Your login:') ?> <b><?= $user_login ?></b><br>
        <?= __('Your password:') ?> <b><?= __('The one you specified when registering') ?></b>
    </p>
    <p>
        <?= __('To activate your account, please confirm your registration on the %s website.', $config['copyright']) ?>
    </p>
    <p style="text-align: center;">
        <a class="button" href="<?= $link_to_confirm ?>"
           style="background-color:#66615b;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:60px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;">
            <?= __('Confirm') ?>
        </a>
    </p>
    <p>
        <?= __('If you have not registered on our site, please ignore this email. In this case, the account will not be activated and will be automatically deleted.') ?>
    </p>
@endsection
