@extends('install::layout')
@section('content')
    <p>
        <?= __('Congratulations!') ?><br>
        <?= __('JohnCMS %s installation completed successfully.', CMS_VERSION) ?><br>
    </p>
    <div class="alert alert-danger d-inline-block">
        <b><?= __('ATTENTION!') ?></b>
        <div><?= __('Now you need to delete the folder <b>/install/</b>') ?></div>
    </div>
    <p>
        <?= __('If you need help, you can visit <a href="https://johncms.com/forum/" class="text-underline">our forum</a>.') ?><br>
        <?= __('Also, if necessary, you can familiarize yourself with <a href="https://johncms.com/documentation/" class="text-underline">the documentation</a>') ?>
    </p>
    <p>
        <a href="/" class="btn btn-primary"><?= __('Go to website') ?></a>
    </p>
@endsection
