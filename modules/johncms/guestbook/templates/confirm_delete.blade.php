<form action="<?= $actionUrl ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <div class="modal-header align-items-center">
        <h4 class="modal-title">
            <?= __('Delete message') ?>
        </h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <h4 class="alert alert-danger"><?= __('Do you really want to delete?') ?></h4>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><?= __('Delete') ?></button>
        <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-outline-primary ms-2"><?= __('Cancel') ?></button>
    </div>
</form>
