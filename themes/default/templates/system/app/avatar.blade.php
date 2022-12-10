<?php

/**
 * @var $avatar_url
 * @var $username
 * @var $first_symbols
 * @var $color
 */

?>
<?php if (! empty($avatar_url)): ?>
    <div class="avatar">
        <img src="<?= $avatar_url ?>" class="img-fluid" alt=".">
    </div>
<?php else: ?>
    <div class="avatar">
        <div class="text-white text-avatar"
             style="background: <?= $color ?>;"
        ><?= $first_symbols ?></div>
    </div>
<?php endif; ?>

