@extends('system::layout/default')
@section('content')
    <div>
        <div class="row">
            <div class="col-md-6">
                <?php if ($data['result']): ?>
                <div class="alert alert-success"><?= __('The module has been successfully removed') ?></div>
                <?php else: ?>
                <div class="alert alert-danger"><?= __('An error occurred while removing the module. Check the log for details.') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <button class="btn btn-primary me-1" type="button" data-bs-toggle="collapse" data-bs-target="#showInstallLog" aria-expanded="false">
                <?= __('Log') ?>
            </button>
            <a href="<?= route('admin.modules') ?>" class="btn btn-outline-secondary"><?= __('Back') ?></a>
        </div>
        <div class="collapse" id="showInstallLog">
            <div class="card card-body">
                <pre><?= $data['log'] ?></pre>
            </div>
        </div>
    </div>
@endsection
