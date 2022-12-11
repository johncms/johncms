<?php

/**
 * @var array $data
 * @var array $config
 */

?>
@extends('system::layout/default')

@section('content')
    <form method="post" class="mb-3" action="<?= $data['storeUrl'] ?>">
        <h4><?= __('Registration') ?></h4>

        @if($data['moderation'])
            <div class="alert alert-warning"><?= __('You can get authorized on the site after confirmation of your registration.') ?></div>
        @endif

        <div class="alert alert-info">
            <?= __('Please, do not register names like 111, shhhh, uuuu, etc. They will be deleted. <br /> Also all the profiles registered via proxy servers will be deleted') ?>
        </div>

        @include('system::forms/simple_form', [
            'fields' => $data['formFields'],
            'errors' => $data['validationErrors'],
        ])

        <div class="mb-3">
            <?= __('By signing up, you agree to the <a href="%s">Terms of Service</a> and <a href="%s">Privacy Policy</a>, including <a href="%s">Cookie Use</a>.', $data['tosUrl'], $data['privacyUrl'], $data['cookieUrl']) ?>
        </div>

        <button type="submit" name="submit" class="btn btn-primary"><?= __('Registration') ?></button>
    </form>
@endsection
