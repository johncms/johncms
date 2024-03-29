<?php

/**
 * @var $user_name
 * @var $link_to_restore
 * @var $config
 */

?>
@extends('system::mail/layouts/default', ['email_title' => __('Password recovery')])

@section('content')
    <?= __('Hello, %s!', $user_name) ?>
    <p>
        <?= __('You start process of password recovery on the <a href="%s">%s</a> website', $config['homeurl'], $config['copyright']) ?>
    </p>
    <p>
        <?= __('In order to recover your password, you must click on the link:') ?>
    </p>
    <p style="text-align: center;">
        <a class="button" href="<?= $link_to_restore ?>"
           style="background-color:#66615b;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:60px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;">
            <?= __('Restore') ?>
        </a>
    </p>
    <p><?= __('Link valid for 1 hour') ?></p>
    <p>
        <?= __('If you receive this mail by mistake, just ignore this letter') ?>
    </p>
@endsection
