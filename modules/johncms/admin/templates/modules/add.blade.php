@extends('system::layout/default')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <form method="post" class="mb-3" action="<?= $data['storeUrl'] ?>">
                @include('system::forms/simple_form', [
                    'fields' => $data['formFields'],
                    'errors' => $data['validationErrors'],
                ])
                <div class="mt-3">
                    <button type="submit" name="submit" class="btn btn-primary me-1"><?= __('Add') ?></button>
                    <a href="<?= route('admin.modules') ?>" class="btn btn-outline-secondary"><?= __('Back') ?></a>
                </div>
            </form>
        </div>
    </div>
@endsection
