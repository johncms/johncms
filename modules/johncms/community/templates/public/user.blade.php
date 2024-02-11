<div class="new_post-item">
    <div class="new_post-header d-flex justify-content-between">
        <div class="post-user">
            @if($user->id !== $currentUser?->id)
                <a href="{{ route('personal.profile', ['id' => $user->id]) }}">
                    <div class="user_photo border rounded-circle overflow-hidden">
                        <x-avatar :avatar-url="$user->avatar_url" :username="$user->displayName()"/>
                    </div>
                </a>
            @else
                <div class="user_photo border rounded-circle overflow-hidden">
                    <x-avatar :avatar-url="$user->avatar_url" :username="$user->displayName()"/>
                </div>
            @endif
            <span class="user-status {{ $user->is_online ? 'online' : 'offline' }} shadow"></span>
            @if($user->isAdmin())
                <div class="post-of-user"
                     data-bs-toggle="tooltip"
                     data-bs-placement="top"
                     data-bs-html="true"
                     title="{{ $user->role_names }}">
                    <svg class="icon-post">
                        <use xlink:href="{{ asset('icons/sprite.svg') }} ?4#check"/>
                    </svg>
                </div>
            @endif
        </div>
        <div class="flex-grow-1 post-user d-flex flex-wrap overflow-hidden d-flex align-items-center">
            <div class="w-100">
                @if($user->id !== $currentUser?->id)
                    <a href="{{ route('personal.profile', ['id' => $user->id]) }}"><span
                            class="user-name d-inline me-2">{{ $user->displayName() }}</span></a>
                @else
                    <div class="user-name d-inline me-2">{{ $user->displayName() }}</div>
                @endif
            </div>
            @if (!empty($item->status))
                <div class="overflow-hidden text-nowrap text-dark-brown overflow-ellipsis small">
                    <span class="fw-bold">{{ $user->status }}</span>
                </div>
            @endif
        </div>
    </div>
    <?php if (isset($activeTab)): ?>
    <div class="post-body p-0">
        @if ($activeTab === 'guest')
            {{ $user->guest_message_count }} {{  n__('post', 'posts', $user->guest_message_count) }}
        @elseif ($activeTab === 'comm')
            {{ $user->comment_count }} {{  n__('comment', 'comments', $user->comment_count) }}
        @elseif ($activeTab === 'karma')
            {{ $item['karma'] }} {{ n__('point', 'points', $item['karma']) }}
        @elseif ($activeTab === 'forum')
            {{  $user->forum_posts_count }} {{ n__('post', 'posts', $user->forum_posts_count) }}
        @endif
    </div>
    <?php endif; ?>
    <div class="post-footer d-flex justify-content-between">
        <div class="overflow-hidden">
            @if ($currentUser?->isAdmin())
                <div class="post-meta d-flex">
                    <div class="user-ip me-2">
                        <a href="#">{{ $user->activity?->ip }}</a>
                        @if (! empty($user->activity->ip_via_proxy))
                            / <a href="#">{{ $user->activity->ip_via_proxy }}</a>
                        @endif
                    </div>
                    <div class="useragent">
                        <span>{{ $user->browser }} </span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
