@extends('system::layout/guest')
@section('content')
    <div class="min-vh-100 d-flex align-items-center">
        <div class="card w-100 mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <form method="post" class="mb-3" action="<?= $data['storeUrl'] ?>">
                    <h4><?= __('Authorization') ?></h4>

                    <?php if (! empty($data['authError'])): ?>
                        @include('system::app/alert', ['alert_type' => 'alert-danger', 'alert' => $data['authError']])
                    <?php endif ?>

                    @include('system::forms/simple_form', [
                        'fields' => $data['formFields'],
                        'errors' => $data['validationErrors'],
                    ])
                    <button type="submit" name="submit" class="btn btn-primary"><?= __('Log in') ?></button>
                </form>
            </div>
        </div>
    </div>
@endsection

