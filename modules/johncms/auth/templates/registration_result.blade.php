<?php

/**
 * @var bool $moderation
 * @var bool $email_confirmation
 * @var Johncms\Users\User $user
 */

?>
@extends('system::layout/default')
@section('content')
    <p>
        <?= __('Congratulations! You have successfully registered on the site.') ?>
    </p>
    @if($email_confirmation)
        <div class="alert alert-warning">
            {{ __('Now you just need to confirm your email address.<br> Please check your email and follow the instructions in it.') }}
        </div>
    @elseif($moderation)
        <div class="alert alert-warning"><?= __('Please, wait until a moderator approves your registration') ?></div>
    @else
        <a href="/" class="btn btn-primary"><?= __('Enter') ?></a>
    @endif
@endsection
