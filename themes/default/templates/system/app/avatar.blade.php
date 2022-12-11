<?php

/**
 * @var $avatar_url
 * @var $username
 * @var $first_symbols
 * @var $color
 */

?>
<?php if (! empty($avatarUrl)): ?>
    <div class="avatar">
        <img src="<?= $avatarUrl ?>" class="img-fluid" alt=".">
    </div>
<?php else: ?>
    <div class="avatar">
        <div class="text-white text-avatar"
             style="background: <?= $color ?>;"
        ><?= $firstSymbol ?></div>
    </div>
<?php endif; ?>

