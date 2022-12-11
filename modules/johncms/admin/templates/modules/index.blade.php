@extends('system::layout/default')
@section('content')
    <div class="mb-4">
        <a href="<?= route('admin.modules.add') ?>" class="btn btn-primary"><?= __('Add') ?></a>
    </div>

    @foreach ($data['modules'] as $module)
        <div class="border-bottom mb-3 pb-3">
            <h4 class="fw-bolder">{{$module->name}} ({{$module->version}})</h4>
            <div class="body small pb-2">
                @if (! empty($module->repoVersion))
                    <div><?= __('New version:') ?> {{$module->repoVersion}}</div>
                @endif
                @if (! empty($module->license))
                    <div><?= __('License:') ?> {{$module->license}}</div>
                @endif
                @if (! empty($module->description))
                    <div><?= __('Description:') ?> {{$module->description}}</div>
                @endif
                @if (! empty($module->homepage))
                    <div><?= __('Homepage:') ?> {{$module->homepage}}</div>
                @endif
            </div>
            @if (! $module->isSystem)
                <div class="actions">
                    @if ($module->updateAvailable)
                        <a href="<?= route('admin.modules.update', queryParams: ['name' => $module->name]) ?>"
                           class="btn btn-warning btn-sm me-1"><?= __('Update') ?></a>
                    @endif
                    <a href="<?= route('admin.modules.delete', queryParams: ['name' => $module->name]) ?>"
                       class="btn btn-danger btn-sm"><?= __('Delete') ?></a>
                </div>
            @endif
        </div>
    @endforeach
@endsection

