<?php

/**
 * @var $user_name
 * @var $user_login
 * @var $link_to_confirm
 * @var $config
 */

?>
@extends('system::mail/layouts/default', ['email_title' => __('Changing email address')])
@section('content')
    <?= __('Hello, %s!', $user_name) ?>
    <p>
        <?= __('You have started the process of changing the email address on the <a href="%s">%s</a> website', $config['homeurl'], $config['copyright']) ?>
    </p>
    <p>
        <?= __('The new email address is:') ?> <b><?= $new_email ?></b>
    </p>
    <p>
        <?= __('If you have not started the procedure for changing email, please contact us in any way convenient for you.') ?>
    </p>
@endsection

