@extends('system::layout/default')
@section('content')
    <form method="post" enctype="multipart/form-data" class="mb-3" action="<?= $data['confirmUrl'] ?>">
        <div class="alert alert-warning"><?= __('Are you sure you want to leave the site?') ?></div>
        @include('system::forms/simple_form', [
            'fields' => [],
            'errors' => [],
        ])
        <button type="submit" name="submit" class="btn btn-primary"><?= __('Confirm') ?></button>
        <a href="<?= route('homepage.index') ?>" class="btn btn-outline-secondary"><?= __('Cancel') ?></a>
    </form>
@endsection
