<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * @var $title
 * @var $page_title
 * @var $data
 */

?>
@extends('system::layout/default')
@section('content')
    @include('johncms/online::tabs')
    <!-- List of Users -->
    <?php if ($data['total'] > 0): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="list-group">
                    <?php foreach ($data['items'] as $item): ?>
                <div class="list-group-item d-flex align-items-center <?= $item['current_user_ip'] ? 'bg-yellow-light' : '' ?>">
                    <div class="me-3">
                        <a href="<?= $item['search_ip'] ?>"><?= $item['ip'] ?></a> [<a href="<?= $item['whois_ip'] ?>"> ? </a>]
                    </div>
                    <div>
                        (<?= $item['count'] ?>)
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-info"><?= __('List is empty') ?></div>
        </div>
    </div>
    <?php endif ?>

    <?php if ($data['total'] > 0): ?>
    <div>
        <div class="my-2"><?= __('Total') ?>: <?= $data['total'] ?></div>
            <?= $data['pagination'] ?>
    </div>
    <?php endif; ?>
@endsection



