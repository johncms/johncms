@extends('system::layout/default')
@section('content')
    <form method="post" class="mb-3 mw-750px" action="<?= $data['storeUrl'] ?>" enctype="multipart/form-data">
        @include('system::forms/simple_form', [
            'fields' => $data['formFields'],
            'errors' => $data['validationErrors'],
        ])
        <div class="mt-3">
            <button type="submit" name="submit" class="btn btn-primary"><?= __('Save') ?></button>
            <a href="<?= $data['backUrl'] ?>" class="btn btn-outline-secondary"><?= __('Back') ?></a>
        </div>
    </form>
@endsection
