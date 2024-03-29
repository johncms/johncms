<?php

/**
 * @var $item
 * @var \Johncms\Users\User $user
 */

?>
<div class="user-row">
    <div class="d-flex align-items-start">
        <div class="user-avatar me-2 me-md-4">
            <div class="avatar-image rounded-circle overflow-hidden">
                <x-avatar :avatar-url="$item['avatar_url']" :username="$item['name']"/>
            </div>
            <div class="user-status <?= $item['is_online'] ? 'online' : 'offline' ?> shadow"></div>
        </div>
        <div class="overflow-auto flex-grow-1">
            <div class="user-name">
                <?php if ($user?->id !== $item['id']): ?>
                    <a href="<?= $item['profile_url'] ?>" class="user-login me-2"><?= $item['name'] ?></a>
                <?php else: ?>
                    <a class="user-login me-2"><?= $item['name'] ?></a>
                <?php endif; ?>
                <span class="text-muted"><?= $item['time'] ?></span>
            </div>
            <?php if (! empty($item['place_name'])): ?>
                <div>
                    <?php if (empty($item['place_url'])): ?>
                        <?= $item['place_name'] ?>
                    <?php else: ?>
                        <a href="<?= $item['place_url'] ?>"><?= $item['place_name'] ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($user?->hasAnyRole()): ?>
        <div class="post-footer d-flex justify-content-between border-top pt-2 mt-2">
            <div class="post-user-info d-flex overflow-hidden small align-items-center">
                <div class="user-ip me-2">
                    <a href="<?= $item['search_ip_url'] ?>"><?= $item['ip'] ?></a>
                    <?php if (! empty($item['ip_via_proxy'])): ?>
                        / <a href="<?= $item['search_ip_via_proxy_url'] ?>"><?= $item['ip_via_proxy'] ?></a>
                    <?php endif; ?>
                </div>
                <div class="useragent"><?= $item['user_agent'] ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>
