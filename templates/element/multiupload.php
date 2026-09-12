<?php
/**
 * Multi-upload button with sequential progress modal
 *
 * @var \Brammo\Admin\View\AppView $this
 * @var string $id
 * @var string|null $btnClass
 * @var string|null $icon
 * @var string $label
 * @var string|array|null $url
 * @var string|null $accept
 */

use Cake\Routing\Router;
use function Cake\Core\h;
use function Cake\I18n\__d;

$id = $id ?? 'files';
$btnClass = $btnClass ?? 'btn-info';
$icon = $icon ?? 'upload';
$label = $label ?? __d('brammo/admin', 'Upload');
$url = $url ?? null;
$accept = $accept ?? null;

if (is_array($url)) {
    $url = Router::url($url);
}

$this->Html->script('Brammo/Admin.multiupload', ['block' => true]);

$csrfToken = $this->getRequest()->getAttribute('csrfToken');
$modalId = 'multiupload-modal-' . $id;
?>
<span
    class="multiupload-widget d-inline-flex align-items-center"
    data-multiupload
    data-url="<?= h((string)$url) ?>"
    data-csrf-token="<?= h((string)$csrfToken) ?>"
    data-modal="#<?= h($modalId) ?>"
    data-label-uploading="<?= h(__d('brammo/admin', 'Uploading…')) ?>"
    data-label-processing="<?= h(__d('brammo/admin', 'Processing…')) ?>"
    data-label-done="<?= h(__d('brammo/admin', 'Done')) ?>"
    data-label-failed="<?= h(__d('brammo/admin', 'Failed')) ?>"
    data-label-cancelled="<?= h(__d('brammo/admin', 'Cancelled')) ?>"
    data-label-progress="<?= h(__d('brammo/admin', 'Uploading {n} of {total}')) ?>"
    data-label-summary-ok="<?= h(__d('brammo/admin', 'Uploaded {n} of {total}')) ?>"
    data-label-summary-fail="<?= h(__d('brammo/admin', 'Failed: {n}')) ?>"
>
    <label class="btn <?= h((string)$btnClass) ?> mb-0" for="upload-input-<?= h($id) ?>">
        <?= $this->Html->icon($icon) ?>
        <span><?= h($label) ?></span>
        <input
            id="upload-input-<?= h($id) ?>"
            type="file"
            name="files[]"
            multiple
            style="display:none"
            <?= $accept ? 'accept="' . h($accept) . '"' : '' ?>
        >
    </label>
</span>

<div
    class="modal fade"
    id="<?= h($modalId) ?>"
    tabindex="-1"
    aria-labelledby="<?= h($modalId) ?>-title"
    aria-hidden="true"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
>
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= h($modalId) ?>-title" data-multiupload-counter>
                    <?= h(__d('brammo/admin', 'Uploading…')) ?>
                </h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="progress" style="height: 1.25rem">
                        <div
                            class="progress-bar progress-bar-striped progress-bar-animated"
                            role="progressbar"
                            data-multiupload-overall
                            style="width: 0%"
                            aria-valuenow="0"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >0%</div>
                    </div>
                </div>
                <div class="list-group list-group-flush" data-multiupload-list></div>
                <template data-multiupload-row>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <div class="text-truncate fw-medium" data-row-name></div>
                            <div class="text-nowrap small text-muted" data-row-size></div>
                        </div>
                        <div class="progress mb-1" style="height: 0.5rem">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                data-row-bar
                                style="width: 0%"
                                aria-valuenow="0"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                        <div class="small text-muted" data-row-status></div>
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-multiupload-cancel>
                    <?= h(__d('brammo/admin', 'Cancel')) ?>
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    data-multiupload-close
                    data-bs-dismiss="modal"
                    style="display:none"
                >
                    <?= h(__d('brammo/admin', 'Close')) ?>
                </button>
            </div>
        </div>
    </div>
</div>
