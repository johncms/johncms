<?php

/**
 * @var $data
 */

?>

@extends('system::layout/default')
@section('content')
    @include('johncms/online::tabs')

    <!-- List of Users -->
    <?php
    if (! empty($data['total'])): ?>
        <?php
    foreach ($data['users'] as $item): ?>
    @include('johncms/online::user_row', ['item' => $item])
    <?php
    endforeach ?>
    <?php
    else: ?>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-info"><?= __('List is empty') ?></div>
        </div>
    </div>
    <?php
    endif ?>

    <?php
    if ($data['total'] > 0): ?>
    <div>
        <div class="my-2"><?= __('Total') ?>: <?= $data['total'] ?></div>
        <!-- Page switching -->
            <?= $data['pagination'] ?>
    </div>
    <?php
    endif; ?>
@endsection


