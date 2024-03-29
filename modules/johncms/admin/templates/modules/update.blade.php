@extends('system::layout/default')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <form method="post" class="mb-3" action="<?= $data['storeUrl'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="alert alert-danger"><?= __('Are you sure you want to update the module <b>%s</b>?', $data['name']) ?></div>


                <div class="mt-3">
                    <button type="submit" name="submit" class="btn btn-danger me-1"><?= __('Confirm') ?></button>
                    <a href="<?= route('admin.modules') ?>" class="btn btn-outline-secondary"><?= __('Back') ?></a>
                </div>
            </form>
        </div>
    </div>
@endsection
