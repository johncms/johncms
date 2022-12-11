<?php
/**
 * @var $alert
 */
?>
<?php if (! empty($alert)): ?>
    <div class="alert <?= $alert_type ?? '' ?>">
        <?= is_array($alert) ? implode('<br>', $alert) : $alert ?>
    </div>
<?php endif; ?>
