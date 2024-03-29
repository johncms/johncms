@extends('system::layout/default')
@section('content')
    <h2 class="mb-3"><?= __('Required parameters') ?></h2>
    <table class="table table-bordered responsive-table">
        <thead>
        <tr>
            <th scope="col" style="width: 20%;"><?= __('Check name') ?></th>
            <th scope="col"><?= __('Value') ?></th>
            <th scope="col"><?= __('Description') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($data['required_checks'] as $check): ?>
        <tr>
            <th scope="row"><?= $check['name'] ?></th>
            <td data-title="<?= __('Value') ?>" class="break-word <?= $check['error'] ? 'text-danger' : 'text-success' ?>"><?= $check['value'] ?></td>
            <td data-title="<?= __('Description') ?>"><?= $check['description'] ?></td>
        </tr>
        <?php
        endforeach ?>
        </tbody>
    </table>

    <h2 class="mb-3"><?= __('Database') ?></h2>
    <table class="table table-bordered responsive-table">
        <thead>
        <tr>
            <th scope="col" style="width: 20%;"><?= __('Check name') ?></th>
            <th scope="col" style="width: 20%;"><?= __('Value') ?></th>
            <th scope="col"><?= __('Description') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($data['database'] as $check): ?>
        <tr>
            <th scope="row"><?= $check['name'] ?></th>
            <td data-title="<?= __('Value') ?>" class="break-word <?= $check['error'] ? 'text-danger' : 'text-success' ?>"><?= $check['value'] ?></td>
            <td data-title="<?= __('Description') ?>"><?= $check['description'] ?></td>
        </tr>
        <?php
        endforeach ?>
        </tbody>
    </table>

    <h2 class="mb-3"><?= __('Recommended parameters') ?></h2>
    <table class="table table-bordered responsive-table">
        <thead>
        <tr>
            <th scope="col"><?= __('Check name') ?></th>
            <th scope="col"><?= __('Value') ?></th>
            <th scope="col"><?= __('Description') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($data['recommendations'] as $check): ?>
        <tr>
            <th scope="row"><?= $check['name'] ?></th>
            <td data-title="<?= __('Value') ?>" class="break-word <?= $check['error'] ? 'text-danger' : 'text-success' ?>"><?= $check['value'] ?></td>
            <td data-title="<?= __('Description') ?>"><?= $check['description'] ?></td>
        </tr>
        <?php
        endforeach ?>
        </tbody>
    </table>
@endsection
