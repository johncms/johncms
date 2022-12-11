@extends('system::layout/default')
@section('content')

    @includeWhen($data['is_closed'], 'system::app/alert', [
        'alert_type' => 'alert-danger',
        'alert'      => __('The guestbook is closed'),
    ])

    @includeWhen($data['message'],  'system::app/alert', [
        'alert_type' => 'alert-success',
        'alert'      => $data['message'],
    ])

    @includeWhen((! $data['can_write'] && !$user), 'system::app/alert', [
       'alert_type' => 'alert-danger',
       'alert'      => __('For registered users only'),
    ])

    @if($data['can_write'])
        <div class="row">
            <div class="col-12">
                @if (! empty($data['errors']['csrf_token']))
                    <div class="alert alert-danger"><?= implode(', ', $data['errors']['csrf_token']) ?></div>
                @endif
                <form name="form" action="<?= $data['actionUrl'] ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"/>

                    @if (! $user)
                        <div class="mb-2">
                            <label for="name" class="form-label"><?= __('Name') ?></label>
                            <input type="text"
                                   class="form-control <?= ! empty($data['errors']['name']) ? 'is-invalid' : '' ?>"
                                   maxlength="25"
                                   name="name"
                                   id="name"
                                   required
                                   value="{{ $data['form_data']['name'] }}"
                                   placeholder="<?= __('Name') ?>"
                            >
                            @if (! empty($data['errors']['name']))
                                <div class="invalid-feedback d-block"><?= implode(', ', $data['errors']['name']) ?></div>
                            @endif
                        </div>
                    @endif

                    @include('johncms/guestbook::ckeditor', [
                        'label'     => __('Message'),
                        'errors'    => implode(', ', $data['errors']['message'] ?? []),
                        'value'     => e($data['form_data']['message']),
                        'uploadUrl' => $data['uploadUrl'],
                    ])

                    @if (! $user)
                        <img alt="<?= __('Verification code') ?>" src="<?= $data['captcha'] ?>">
                        <div>
                            <label for="code" class="form-label"><?= __('Symbols on the picture') ?></label>
                            <input type="text"
                                   class="form-control <?= (isset($data['errors']['code']) ? 'is-invalid' : '') ?>"
                                   maxlength="6"
                                   name="code"
                                   id="code"
                                   autocomplete="off"
                                   required
                                   placeholder="<?= __('Symbols on the picture') ?>"
                            >
                            @if (isset($data['errors']['code']))
                                <div class="invalid-feedback"><?= implode(', ', $data['errors']['code']) ?></div>
                            @endif
                            <div class="text-muted small">
                                {{ __('If you cannot see the image code, enable graphics in your browser and refresh this page') }}
                            </div>
                        </div>
                    @endif

                    <input type="submit" class="btn btn-primary mt-2" name="submit" value="<?= __('Write') ?>"/>
                </form>
            </div>
        </div>
    @endif

    <div class="mt-4 border-bottom full-mobile-width"></div>
    <!-- Отображаем сообщения -->
    @forelse ($data['posts']['data'] as $post)
        <div class="new_post-item">
            <div class="new_post-header d-flex justify-content-between">
                <div class="post-user">
                    @if (! empty($post['user']) && ! empty($post['user']['profile_url']))
                        <a href="<?= $post['user']['profile_url'] ?>">
                            <x-avatar :avatar-url="$post['user']['avatar_url']" :username="$post['name']"/>
                        </a>
                    @else
                        <x-avatar :username="$post['name']"/>
                    @endif
                    <span class="user-status <?= $post['is_online'] ? 'online' : 'offline' ?> shadow"></span>
                    @if (! empty($post['user']['rights_name']))
                        <div class="post-of-user"
                             data-bs-toggle="tooltip"
                             data-bs-placement="top"
                             data-bs-html="true"
                             title="<?= $post['user']['rights_name'] ?>">
                            <svg class="icon-post">
                                <use xlink:href="<?= asset('icons/sprite.svg') ?>?4#check"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="flex-grow-1 post-user d-flex flex-wrap overflow-hidden d-flex align-items-center">
                    <div class="w-100">

                        @if (! empty($post['user']) && ! empty($post['user']['profile_url']))
                            <a href="<?= $post['user']['profile_url'] ?>" class="user-name d-inline me-2"><?= $post['name'] ?></a>
                        @else
                            <div class="user-name d-inline me-2"><?= (__('Guest') . ': ' . $post['name']) ?></div>
                        @endif
                        <span class="post-meta d-inline me-2"><?= $post['created_at'] ?></span>
                        @if (! empty($post['edit_count']))
                            <div class="post-meta d-inline me-2"
                                 data-bs-toggle="tooltip"
                                 data-bs-placement="top"
                                 data-bs-html="true"
                                 title="<?= __('Edited:') ?> <?= $post['edited_by'] ?> <br> (<?= $post['edited_at'] ?>) [<?= $post['edit_count'] ?>]">
                                    <?= __('Edited') ?>
                            </div>
                        @endif
                    </div>

                    @if (! empty($post['user']) && ! empty($post['user']['status']))
                        <div class="overflow-hidden text-nowrap text-dark-brown overflow-ellipsis small">
                            <span class="fw-bold"><?= $post['user']['status'] ?></span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="post-body last-p-no-margin pt-2">
                {!! $post['text'] !!}
                @if (! empty($post['reply_text']))
                    <div class="alert alert-warning mt-2 mb-n2">
                        <b><?= $post['reply_author'] ?></b> <?= $post['replied_at'] ?><br><?= $post['reply_text'] ?>
                    </div>
                @endif
            </div>
            <div class="post-footer d-flex justify-content-between mt-2">
                <div class="overflow-hidden">
                    @if ($post['meta'])
                        <div class="post-meta d-flex">
                            <div class="user-ip me-2">
                                <a href="<?= $post['meta']['search_ip_url'] ?>"><?= $post['meta']['ip'] ?></a>
                            </div>
                            <div class="useragent">
                                <span><?= $post['meta']['user_agent'] ?></span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="d-flex">
                    @if ($data['can_write'] && $post['user_id'] !== $user?->id)
                        <div class="ms-3">
                            <a href="#" onclick="reply_to_post('<?= $post['name'] ?>')"><?= __('Reply') ?></a>
                        </div>
                    @endif
                    @if (! empty($post['meta']['can_manage']))
                        <div class="dropdown ms-3">
                            <div class="cursor-pointer" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg class="icon text-primary">
                                    <use xlink:href="<?= asset('icons/sprite.svg') ?>?1#more_horizontal"/>
                                </svg>
                            </div>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if (isset($post['meta']['reply_url']))
                                    <a href="<?= $post['meta']['reply_url'] ?>" class="dropdown-item"><?= __('Reply') ?></a>
                                @endif
                                <a href="<?= $post['meta']['edit_url'] ?>" class="dropdown-item"><?= __('Edit') ?></a>
                                <a href="#" data-url="<?= $post['meta']['delete_url'] ?>" data-bs-toggle="modal"
                                   data-bs-target=".ajax_modal" class="dropdown-item"><?= __('Delete') ?></a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info mt-3"><?= __('The guestbook is empty.<br><strong>Be the first! :)</strong>') ?></div>
    @endforelse

    @if (! empty($data['pagination']))
        <div class="mt-4"><?= $data['pagination'] ?></div>
    @endif

    @if ($data['can_clear'])
        <div class="d-flex mt-4 pb-2">
            <button type="button" class="btn btn-danger" data-url="<?= $data['cleanUrl'] ?>" data-bs-toggle="modal"
                    data-bs-target=".ajax_modal"><?= __('Clear') ?></button>
        </div>
    @endif

    @push('scripts')
        <script>
            function reply_to_post(user_name) {
                event.preventDefault();
                $('body,html').animate({scrollTop: 100}, 500);
                editor.editing.view.focus();
                editor.model.change(writer => {
                    const insertPosition = editor.model.document.selection.getFirstPosition();
                    writer.insertText(user_name + ', ', {}, insertPosition);
                    writer.setSelection(writer.createPositionAt(editor.model.document.getRoot(), 'end'));
                });
            }
        </script>
    @endpush
@endsection
