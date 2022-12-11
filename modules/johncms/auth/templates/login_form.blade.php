@extends('system::layout/default')
@section('content')
    <form method="post" class="mb-3" action="<?= $data['storeUrl'] ?>">
        <h4><?= __('Authorization') ?></h4>

        @if($data['authError'])
            @include('system::app/alert', ['alert_type' => 'alert-danger', 'alert' => $data['authError']])
        @endif

        @include('system::forms/simple_form', [
            'fields' => $data['formFields'],
            'errors' => $data['validationErrors'],
        ])

        <button type="submit" name="submit" class="btn btn-primary"><?= __('Log in') ?></button>
        <a href="/profile/skl.php?continue" class="btn btn-link"><?= __('Forgot password?') ?></a>
        <div class="mt-3">
            <a href="<?= $data['registrationUrl'] ?>" class="me-2"><?= __('Registration') ?></a>
        </div>
    </form>
@endsection
