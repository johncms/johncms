<?php

/**
 * @var $user_name
 * @var $user_login
 * @var $user_password
 * @var $config
 */

$this->layout();
?>
@extends('system::mail/layouts/default', ['email_title' => __('Password recovery')])
@section('content')
    <?= __('Hello, %s!', $user_name) ?>
    <p>
        <?= __('You have changed your password on the <a href="%s">%s</a> website', $config['homeurl'], $config['copyright']) ?>
    </p>
    <p>
        <?= __('Your login:') ?> <b><?= $user_login ?></b><br>
        <?= __('Your password:') ?> <b><?= $user_password ?></b>
    </p>
    <p>
        <?= __('After logging in, you can change your password to new one.') ?>
    </p>
@endsection
