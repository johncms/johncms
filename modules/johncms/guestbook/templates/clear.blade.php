<form action="<?= $actionUrl ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <div class="modal-header align-items-center">
        <h4 class="modal-title">
            <?= __('Clear guestbook') ?>
        </h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <p><?= __('Clearing parameters') ?></p>
        <div class="custom-control custom-radio">
            <input type="radio" id="week" name="cl" value="0" class="form-check-input" checked>
            <label class="form-check-label" for="week"><?= __('Older than 1 week') ?></label>
        </div>
        <div class="custom-control custom-radio">
            <input type="radio" id="day" name="cl" value="1" class="form-check-input">
            <label class="form-check-label" for="day"><?= __('Older than 1 day') ?></label>
        </div>
        <div class="custom-control custom-radio">
            <input type="radio" id="all" name="cl" value="2" class="form-check-input">
            <label class="form-check-label" for="all"><?= __('Clear all') ?></label>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><?= __('Clear') ?></button>
        <button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn btn-outline-primary ms-2"><?= __('Cancel') ?></button>
    </div>
</form>
